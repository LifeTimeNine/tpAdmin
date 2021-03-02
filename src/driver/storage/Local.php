<?php

namespace tpadmin\driver\storage;

use SplFileObject;
use think\facade\Cache;
use tpadmin\service\Config;

/**
 * 文件存储 本地驱动
 * @class Local
 */
class Local
{
    /**
     * 配置
     * @var Config
     */
    protected $config;
    /**
     * 允许的后缀
     * @var array
     */
    protected $allowExts = [];
    /**
     * 禁止的后缀
     * @var array
     */
    protected $forbidExts = ['php', 'sh'];
    /**
     * 保存路径
     * @var string
     */
    protected $savePath = '';
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->config = Config::instance();
        $this->allowExts = explode('|', $this->config->get('storage_allowExts'));
        $this->forbidExts = explode('|', $this->config->get('storage_forbidExts'));
        $this->savePath = $this->config->get('storage_local_savePath');
    }
    /**
     * 构建上传参数
     * @param   string  $fileName   上传的文件名称
     * @param   string  $saveName   保存的名称
     * @return  array
     */
    public function bulidUploadParams($fileName, $saveName)
    {
        $url = request()->domain() . "/upload/uploadFile";
        $body = [
            'token' => $this->buildToken($fileName),
            'fileName' => $saveName
        ];
        return [
            'url' => $url,
            'body' => $this->arrToKeyVal($body),
            'fileFieldName' => 'file',
            'filePath' => request()->domain()."/{$this->savePath}/{$saveName}",
        ];
    }
    /**
     * 构建分片上传ID
     * @param   string  $fileName   上传的文件名称
     * @param   string  $saveName   保存的名称
     * @return  string
     */
    public function bulidShardUploadId($fileName, $saveName)
    {
        $uploadId = md5(uniqid("{$fileName}{$saveName}"));
        if (!is_dir(env('runtime_path') . "/shard/{$uploadId}")) {
            mkdir(env('runtime_path') . "/shard/{$uploadId}", 0777, true);
        }
        return ['uploadId' => $uploadId, 'filePath' => request()->domain()."/{$this->savePath}/{$saveName}"];
    }
    /**
     * 构建token
     * @param   string  $fileName   上传文件名称
     * @return  string
     */
    protected function buildToken($fileName)
    {
        $param = [
            'fileName' => $fileName,
        ];
        $token = md5(json_encode($param));
        Cache::set($token, $param, 60 * 5);
        return $token;
    }

    /**
     * 保存文件
     */
    public function saveFile()
    {
        $token = request()->param('token');
        $saveName = request()->param('fileName');
        $file = request()->file('file');
        if ($this->checkFile($file, $token) !== true) return [false, '文件验证失败,请稍后再试！'];
        $savePath = env('root_path') . "/public/{$this->savePath}/";
        if ($file->move($savePath, $saveName)) {
            return [true, ['url' => request()->domain()."/{$this->savePath}/{$saveName}"]];
        } else {
            return [false, $file->getError()];
        }
    }

    /**
     * 获取分片参数
     * @string  string  $saveName           保存的文件名称
     * @param   string  $uploadId           上传标识
     * @param   int     $beginPartNumber    开始partNumber标识
     * @param   int     $endPartNumber      结束partNUmber标识
     */
    public function getShardOptions($saveName, $uploadId, $beginPartNumber, $endPartNumber)
    {
        if ($endPartNumber <= $beginPartNumber) return [false, 'PartNumber identification error'];
        $options = [];
        for($i = $beginPartNumber; $i <= $endPartNumber; $i++) {
            $options[] = [
                'url' => url("/upload/shardUpload?uploadId={$uploadId}&partNumber={$i}", [], false, true),
                'partNumber' => $i,
            ];
        }
        return [true, $options];
    }

    /**
     * 保存分片文件
     */
    public function saveShard()
    {
        $uploadId = request()->get('uploadId');
        $param = Cache::get($uploadId);
        if (empty($param)) return [false, 'The unknown uploadId'];
        $partNumber = request()->get('partNumber');
        if (empty($partNumber)) return [false, "The partNumber parameter is missing"];
        $data = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents('PHP://input');
        $eTag = md5($data);
        file_put_contents(env('runtime_path') . "/shard/{$uploadId}/{$eTag}-{$partNumber}.data", $data);
        return [true, ['etag' => $eTag]];
    }

    /**
     * 完成分片上传
     * @param   string  $saveName   保存文件名称
     * @param   string  $uploadId   任务id
     * @param   array   $data       上传的数据
     * @return  array
     */
    public function completeShard($saveName, $uploadId, $data)
    {
        $shardPath = env('runtime_path') . "/shard/{$uploadId}/";
        $file = new SplFileObject("{$shardPath}/merge.data", 'wb');
        foreach ($data as $partNumber => $etag)
        {
            $shardData = @file_get_contents("{$shardPath}/{$etag}-{$partNumber}.data");
            if ($shardData === false) return [false, "No sharding data with ETag as {$etag} was found"];
            $file->fwrite($shardData);
        }
        $file = null;
        $saveDir = env('root_path') . "/public/{$this->savePath}";
        if (!is_dir("{$saveDir}/" . pathinfo($saveName, PATHINFO_DIRNAME))) {
            @mkdir("{$saveDir}/" . pathinfo($saveName, PATHINFO_DIRNAME), 0777, true);
        }
        $moveRes = rename("{$shardPath}/merge.data", "{$saveDir}/{$saveName}");
        if (!$moveRes) return [false, "File merge failed"];
        $this->deldir($shardPath);
        return [true, ['filePath' => request()->domain() . "{$this->savePath}/{$saveName}"]];
    }

    /**
     * 验证文件
     * @param   think\File  $file
     * @param   string      $token
     * @return  boolean
     */
    protected function checkFile($file, $token)
    {
        if (empty($file)) return false;
        if (!$file->checkExt($this->allowExts)) return false;
        if ($file->checkExt($this->forbidExts)) return false;
        $tokenData = Cache::get($token);
        if (empty($tokenData)) return false;
        if ($tokenData['fileName'] <> $file->getInfo('name')) return false;
        return true;
    }

    /**
     * 数组转Key，Value形式
     * @param   array   $data
     * @return  array
     */
    protected function arrToKeyVal($data)
    {
        $newData = [];
        foreach($data as $k => $v)
        {
            if (!empty($k)) {
                $newData[] = ['key' => $k, 'value' => $v];
            }
        }
        return $newData;
    }
    /**
     * 删除文件夹及其所有子文件
     * @param  string   $path   文件目录
     */
    protected function deldir($path)
    {
        //如果是目录则继续
        if (is_dir($path)) {
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach ($p as $val) {
                //排除目录中的.和..
                if ($val != "." && $val != "..") {
                    //如果是目录则递归子目录，继续操作
                    if (is_dir($path . '/' . $val)) {
                        //子目录中操作删除文件夹和文件
                        self::deldir($path . '/' . $val);
                        //目录清空后删除空文件夹
                        @rmdir($path . '/' . $val);
                    } else {
                        //如果是文件直接删除
                        unlink($path . '/' . $val);
                    }
                }
            }
            @rmdir($path);
        }
    }
}