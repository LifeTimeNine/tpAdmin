<?php

namespace tpadmin\driver\storage;

use service\ali\oss\Bucket;
use service\ali\oss\object\Basics;
use service\ali\oss\object\Multipart;
use tpadmin\service\Config;

/**
 * OSS存储驱动
 * @class   Oss
 */
class Oss
{
    /**
     * 配置对象
     * @var Config
     */
    protected $configObj;
    /**
     * 配置参数
     * @var array
     */
    protected $config = [];
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->configObj = Config::instance();
        $this->config = [
            'accessKey_id' => $this->configObj->get('storage_oss_keyid'),
            'accessKey_secret' => $this->configObj->get('storage_oss_secret'),
            'oss_bucketName' => $this->configObj->get('storage_oss_bucket'),
            'oss_endpoint' => $this->configObj->get('storage_oss_endpoint'),
            'oss_isSsl' => $this->configObj->get('storage_oss_isSsl'),
        ];
    }

    /**
     * 构建上传参数
     * @param   string  $fileName   上传文件名称
     * @param   string  $saveName   保存名称
     * @return  array
     */
    public function bulidUploadParams($fileName, $saveName)
    {
        return Basics::instance($this->config)->webPut('', $saveName);
    }

    /**
     * 构建分片上传ID
     * @param   string  $fileName   上传的文件名称
     * @param   string  $saveName   保存的名称
     * @return  string
     */
    public function bulidShardUploadId($fileName, $saveName)
    {
        $params = Multipart::instance($this->config)->init('', $saveName);
        return [
            'uploadId' => $params['UploadId'],
            'filePath' => "{$this->getProtocol()}{$this->config['oss_bucketName']}.{$this->config['oss_endpoint']}/{$saveName}",
        ];
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
        $pathinfo = pathinfo($saveName);
        $options = [];
        for($i = $beginPartNumber; $i <= $endPartNumber; $i++) {
            if ($pathinfo['dirname'] == '.') {
                $fileName = "{$pathinfo['filename']}/{$i}.{$pathinfo['extension']}";
            } else {
                $fileName = "{$pathinfo['dirname']}/{$pathinfo['filename']}/{$i}.{$pathinfo['extension']}";
            }
            $options[] = array_merge(Basics::instance($this->config)->webPut('', $fileName), [
                'method' => 'post',
                'contentType' => 'multipart/form-data',
                'partNumber' => $i
            ]);
        }
        return [true, $options];
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
        $pathinfo = pathinfo($saveName);
        if ($pathinfo['dirname'] == '.') {
            $tempDir = "{$pathinfo['filename']}/";
        } else {
            $tempDir = "{$pathinfo['dirname']}/{$pathinfo['filename']}/";
        }
        try {
            $tempList = Bucket::instance()->basics($this->config)->getV2('', '', '', '', '', 1000, $tempDir);
            $tempList = $tempList['Contents'];
            if (count($tempList) <> count($data)) return [false, '文件校验失败 1'];
            $count = count($tempList);
            $eTagList = [];
            $tempFileList = [];
            for ($i = 1; $i <= $count; $i ++) {
                $tempItem = $this->getShardItem($tempList, $i);
                if ($tempItem === false) return [false, '文件上传失败 2'];
                $tempItem['Key'] = urldecode($tempItem['Key']);
                $partNumber = pathinfo($tempItem['Key'], PATHINFO_FILENAME);
                $res = Multipart::instance($this->config)->copy(
                    '',
                    $saveName,
                    $partNumber,
                    $uploadId,
                    '',
                    $tempItem['Key']
                );
                $eTagList[$partNumber] = $res['ETag'];
                $tempFileList[] = $tempItem['Key'];
            }
            Multipart::instance($this->config)->complete('', $saveName, $uploadId, $eTagList);
            Basics::instance($this->config)->deleteMultiple('', $tempFileList);
        } catch (\Exception $e) {
            return [false, '文件上传失败' . $e->getMessage()];
        }
        return [true, ['filePath' => "{$this->getProtocol()}{$this->config['oss_bucketName']}.{$this->config['oss_endpoint']}/{$saveName}"]];
    }

    /**
     * 获取请求协议
     * @return  string
     */
    protected function getProtocol()
    {
        return empty($this->config['oss_isSsl']) ? 'http://' : 'https://';
    }

    /**
     * 根据名称获取切片信息
     * @param   array   $data
     * @param   string  $key
     * @return  array
     */
    private function getShardItem($data, $key)
    {
        foreach ($data as $item) {
            if (pathinfo(urldecode($item['Key']), PATHINFO_FILENAME) == $key) {
                return $item;
            }
        }
        return false;
    }
}