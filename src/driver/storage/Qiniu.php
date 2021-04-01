<?php

namespace tpadmin\driver\storage;

use service\qiniu\storage\Objects;
use tpadmin\service\Config;

/**
 * 七牛对象存储
 * @class   Qiniu
 */
class Qiniu
{
    /**
     * 配置对象
     * @var Config
     */
    protected $configObj;

    /**
     * 配置信息
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
            'accessKey' => $this->configObj->get('storage_qiniu_key'),
            'secretKey' => $this->configObj->get('storage_qiniu_secret'),
            'storage_region' => $this->configObj->get('storage_qiniu_region'),
            'storage_bucketName' => $this->configObj->get('storage_qiniu_bucket'),
            'storage_domain' => $this->configObj->get('storage_qiniu_domain'),
            'stroage_isSsl' => $this->configObj->get('storage_qiniu_isSSl'),
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
        return Objects::instance($this->config)->WebUpload('', $saveName);
    }

    /**
     * 构建分片上传ID
     * @param   string  $fileName   上传的文件名称
     * @param   string  $saveName   保存的名称
     * @return  string
     */
    public function bulidShardUploadId($fileName, $saveName)
    {
        $params = Objects::instance($this->config)->initMultipart('', $saveName);
        return [
            'uploadId' => $params['uploadId'],
            'filePath' => "{$this->getProtocol()}{$this->config['storage_domain']}/{$saveName}",
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
            $options[] = array_merge(Objects::instance($this->config)->webPartParams('',$saveName,$uploadId,$i), [
                'method' => 'put',
                'contrentType' => 'application/octet-stream'
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
        try {
            $res = Objects::instance($this->config)->completePart('', $saveName, $uploadId, $data);
        } catch (\Exception $e) {
            return [false, $e->getMessage()];
        }
        return [true, ['filePath' => "{$this->config['storage_domain']}/{$saveName}"]];
    }

    /**
     * 获取请求协议
     * @return  string
     */
    protected function getProtocol()
    {
        return empty($this->config['isSsl']) ? 'http://' : 'https://';
    }
}