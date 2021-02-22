<?php
namespace tpadmin\service;

use think\facade\Cache;

/**
 * 文件存储服务
 * @class Storage
 */
class Storage
{
    /**
     * 驱动
     * @var object
     */
    protected $driver;
    /**
     * 允许的后缀
     * @var array
     */
    protected $allowExts = [];
    /**
     * 禁止的后缀
     * @var array
     */
    protected $forbidExts = [];
    /**
     * 配置
     * @var Config
     */
    protected $config;
    /**
     * 前缀
     * @var string
     */
    protected $prefix = '';
    /**
     * 分片阈值
     * @var int
     */
    protected $shardAstrict;
    /**
     * 每个分片的大小
     * @var int
     */
    protected $shardSize;

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->config = Config::instance();
        $this->allowExts = explode('|', $this->config->get('storage_allowExts'));
        $this->forbidExts = explode('|', $this->config->get('storage_forbidExts'));
        $this->shardAstrict = $this->config->get('storage_shard_astrict');
        $this->shardSize = round($this->config->get('storage_shard_size') / 4) * 4;
        $this->prefix = $this->config->get('storage_prefix');
        $this->driver = $this->config->get('storage_driver');
    }

    /**
     * 静态实例化对象
     * @return  static
     */
    public static function instance()
    {
        return new static;
    }

    /**
     * 获取实例化的驱动
     * @param   string  $driver
     * @return  object
     */
    protected function getDriver($driver = null)
    {
        if (empty($driver)) $driver = $this->driver;
        $driver = "\\tpadmin\\driver\\storage\\{$driver}";
        if (!class_exists($driver)) throw new \Exception("dirver {$driver} not exists");
        return new $driver;
    }

    /**
     * 获取允许的文件类型
     * @return  string
     */
    public function getAllowExts()
    {
        return $this->allowExts;
    }

    /**
     * 验证文件后缀
     * 
     * @return  boolean|string
     */
    public function checkExt($fileName)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, $this->allowExts) && !in_array($extension, $this->forbidExts);
    }

    /**
     * 获取上传参数
     * @param   string  $fileName   上传的文件的名称
     * @param   string  $fileSize   上传的文件的大小
     * @return array
     */
    public function getUploadParam($fileName, $fileSize)
    {
        if ($fileSize >= $this->shardSize * 1024 * 1024) {
            if ($this->driver == 'Oss') return [false, 'OSS 暂不支持分片上传'];
            return [true, $this->bulidShardParams($fileName, $this->bulidFileName($fileName))];
        } else {
            return [true, $this->getDriver()->bulidUploadParams($fileName, $this->bulidFileName($fileName))];
        }
    }

    /**
     * 构建分片上传参数
     * @param   string  $fileName   上传的文件的名称
     * @param   string  $saveName   保存的文件的名称
     * @return
     */
    protected function bulidShardParams($fileName, $saveName)
    {
        $params = $this->getDriver()->bulidShardUploadId($fileName, $saveName);
        $uploadId = $params['uploadId'];
        Cache::set($uploadId, [
            'saveName' => $saveName,
            'driver' => $this->config->get('storage_driver'),
        ], 3600 * 24 * 7);
        $params['complateUrl'] = request()->domain() . '/complateShardUpload';
        $params['shardSize'] = $this->shardSize * 1024 * 1024;
        return $params;
    }

    /**
     * 获取分片上传的参数
     * @rerntu array
     */
    public function getShardOptions()
    {
        $uploadId = request()->param('uploadId');
        $info = Cache::get($uploadId);
        if (empty($info)) return [false, 'The current UploaID does not exist'];
        $beginPartNumber = request()->param('beginPartNumber/d');
        $endPartNumber = request()->param('endPartNumber/d');
        return $this->getDriver($info['driver'])->getShardOptions($info['saveName'], $uploadId, $beginPartNumber, $endPartNumber);
    }

    /**
     * 完成分片上传
     * @return  array
     */
    public function completeShard()
    {
        $uploadId = request()->param('uploadId');
        $info = Cache::get($uploadId);
        if (empty($info)) return [false, 'The current UploaID does not exist'];
        $etags = request()->param('etags');
        unset($etags[0]);
        return $this->getDriver($info['driver'])->completeShard($info['saveName'], $uploadId, $etags);
    }

    /**
     * 验证并保存文件（只有本地储存有效）
     * return array
     */
    public function saveFile()
    {
        return $this->getDriver('Local')->saveFile();
    }
    /**
     * 保存分片文件(只有本地储存有效)
     * return array
     */
    public function saveShard()
    {
        return $this->getDriver('Local')->saveShard();
    }

    /**
     * 生成新的文件名
     * @param   string  $fileName   上传的文件名
     * @return  string
     */
    protected function bulidFileName($fileName)
    {
        if (!empty($this->prefix)) {
            $prefix = $this->parseMagic(preg_replace('/^(\/|\\\\)/','',$this->prefix,1));
        } else {
            $prefix = '';
        }
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return $prefix . md5(uniqid($fileName)) . ".{$extension}";
    }

    /**
     * 解析魔术变量
     * @param   string  $str
     * @return  string
     */
    protected function parseMagic($str)
    {
        $time = time();
        return strtr($str, [
            '{$date1}' => date('Ymd', $time),
            '{$date2}' => date('Y-m-d', $time),
        ]);
    }
}