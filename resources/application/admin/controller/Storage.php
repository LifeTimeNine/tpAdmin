<?php

namespace app\admin\controller;

use service\Ali;
use service\ali\kernel\BasicOss;
use service\Qiniu;
use service\qiniu\basic\Storage as BasicStorage;
use tpadmin\Controller;
use tpadmin\model\SystemLog;
use tpadmin\service\Config;

/**
 * 文件存储设置
 * @class Storage
 */
class Storage extends Controller
{
    /**
     * 文件存储设置
     * @auth    true
     * @menu    true
     */
    public function index()
    {
        if ($this->request->isGet()) {
            $this->title = '文件存储设置';
            $this->ossEndpointList = BasicOss::OSS_ENDPOINT_LIST;
            $this->qiniuRegionList = BasicStorage::S_REGION_LIST;
            $this->fetch();
        } else {
            $data = $this->request->only([
                'storage_allowExts', 'storage_driver', 'storage_local_savePath','storage_forbidExts',
                'storage_oss_isSsl', 'storage_oss_bucket', 'storage_oss_endpoint', 'storage_oss_keyid',
                'storage_oss_secret', 'storage_oss_domain', 'storage_prefix',
                'storage_qiniu_isSsl', 'storage_qiniu_bucket', 'storage_qiniu_region', 'storage_qiniu_key',
                'storage_qiniu_secret', 'storage_qiniu_domain', 'storage_shard_astrict', 'storage_shard_length'
            ]);
            if ($data['storage_driver'] == "Oss") {
                $this->OssHandle($data);
            }
            if ($data['storage_driver'] == 'Qiniu') {
                $this->qiniuHandle($data);
            }
            Config::instance()->set($data);
            SystemLog::write('系统管理', '编辑文件存储');
            $this->success();
        }
    }

    private function OssHandle($data)
    {
        $accessKey_id = $data['storage_oss_keyid'];
        $accessKey_secret = $data['storage_oss_secret'];
        $endpoint = $data['storage_oss_endpoint'];
        $bucketName = $data['storage_oss_bucket'];
        try {
            $bucketInfo = Ali::oss()->bucket()->basics([
                'accessKey_id' => $accessKey_id,
                'accessKey_secret' => $accessKey_secret,
                'oss_endpoint' => $endpoint,
            ])->getInfo($bucketName);
        } catch (\Exception $e) {
            if ($e->getCode() <> 'NoSuchBucket') {
                $this->error("OSS信息获取失败!{$e->getMessage()}");
            }
            $bucketInfo = null;
        }
        if (empty($bucketInfo)) {
            try {
                Ali::oss()->bucket()->basics([
                    'accessKey_id' => $accessKey_id,
                    'accessKey_secret' => $accessKey_secret,
                    'oss_endpoint' => $endpoint,
                ])->put($bucketName, 'public-read');
            } catch (\Exception $e) {
                if ($e->getCode() == 'BucketAlreadyExists') {
                    $this->error('该Bucket名称已存在或被其他用户占用。 请尝试使用其他符合命名规范的Bucket名称新建 Bucket');
                }
                $this->error("空间创建失败:{$e->getMessage()}");
            }
        } elseif($bucketInfo['Bucket']['AccessControlList']['Grant'] == 'private') {
            try {
                Ali::oss()->bucket()->acl([
                    'accessKey_id' => $accessKey_id,
                    'accessKey_secret' => $accessKey_secret,
                    'oss_endpoint' => $endpoint,
                ])->put($bucketName, 'public-read');
            } catch (\Exception $e) {
                $this->error("权限设置失败:{$e->getMessage()}");
            }
        }
        try {
            Ali::oss()->bucket()->cors([
                'accessKey_id' => $accessKey_id,
                'accessKey_secret' => $accessKey_secret,
                'oss_endpoint' => $endpoint,
            ])->put($bucketName, [
                [
                    'origin' => ['*'],
                    'method' => ['POST', 'PUT'],
                    'allowHeader' => '*',
                    'exposeHeader' => ['ETag']
                ]
            ]);
        } catch (\Exception $e) {
            $this->error("跨域策略设置失败:{$e->getMessage()}");
        }
    }

    private function qiniuHandle($data)
    {
        $accessKey = $data['storage_qiniu_key'];
        $accessSecret = $data['storage_qiniu_secret'];
        $region = $data['storage_qiniu_region'];
        $bucketName = $data['storage_qiniu_bucket'];
        try {
            $bucketList = Qiniu::storage()->service([
                'accessKey' => $accessKey,
                'secretKey' => $accessSecret,
                'storage_region' => $region,
                'storage_bucketName' => $bucketName,
            ])->get();
        } catch (\Exception $e) {
            $this->error("Bucket列表获取失败:{$e->getMessage()}");
            $bucketList = [];
        }
        if (!in_array($bucketName, $bucketList)) {
            try {
                Qiniu::storage()->bucket([
                    'accessKey' => $accessKey,
                    'secretKey' => $accessSecret,
                    'storage_region' => $region,
                ])->create($bucketName, $region);
            } catch (\Exception $e) {
                $this->error("创建Bucket失败: {$e->getMessage()}");
            }
        }
    }
}