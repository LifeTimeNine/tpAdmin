<?php

namespace tpadmin\driver\storage;

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
        $options = [];
        for($i = $beginPartNumber; $i <= $endPartNumber; $i++) {
            $options[] = Multipart::instance($this->config)->webParams('',$saveName,$i,$uploadId);
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
        try {
            $res = Multipart::instance($this->config)->complete('', $saveName, $uploadId, $data);
        } catch (\Exception $e) {
            return [false, $e->getMessage()];
        }
        return [true, ['filePath' => $res['Location']]];
    }

    /**
     * 获取请求协议
     * @return  string
     */
    protected function getProtocol()
    {
        return empty($this->config['oss_isSsl']) ? 'http://' : 'https://';
    }
}