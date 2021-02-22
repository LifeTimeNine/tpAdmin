<?php

use think\Console;
use think\facade\Route;
use tpadmin\service\Config;
use tpadmin\service\Node;
use tpadmin\service\Storage;
use tpadmin\service\Token;

if (!function_exists('sysCsrfToken')) {

    /**
     * 生成 scrf-token
     * @param   string  @node   操作节点
     * @return string
     */
    function sysCsrfToken($node)
    {
        return Token::instance()->build($node);
    }
}
if (!function_exists('check')) {
    /**
     * 验证节点授权
     * @param   string  $node
     * @return  boolean
     */
    function check($node)
    {
        return Node::instance()->check($node);
    }
}
if (!function_exists('sysConfig')) {
    /**
     * 获取配置
     * @param   string  $name   配置名称
     * @param   string  $force  是否强制获取数据库的值
     */
    function sysConfig($name = null, $force = false)
    {
        return Config::instance()->get($name, $force);
    }
}

/**
 * -------------注册路由
 */
if (class_exists('think\Route')) {
    /**
     * 验证文件，并返回上传信息
     */
    Route::rule('upload/uploadCheck', function() {
        $fileName = request()->param('fileName');
        $fileSize = request()->param('fileSize');
        if (!Storage::instance()->checkExt($fileName)) {
            return json(['code' => 0, 'msg' => '禁止的文件类型,请选择其他文件']);
        }
        list($err, $data) = Storage::instance()->getUploadParam($fileName, $fileSize);
        if (!$err) return json(['code' => 0, 'msg' => $data]);
        return json([
            'code' => 1,
            'data' => $data,
        ]);
    });
    /**
     * 本地上传文件
     */
    Route::rule('upload/uploadFile', function() {
        list($state, $data) = Storage::instance()->saveFile();
        if ($state) return json($data);
        return json(['msg' => $data], 403);
    });
    Route::rule('upload/getShardOptions', function() {
        list($state, $data) = Storage::instance()->getShardOptions();
        if ($state) return json(['code' => 1, 'data' => $data]);
        return json(['code' => 0, 'data' => $data]);
    });
    /**
     * 本地分片上传
     */
    Route::rule('upload/shardUpload', function() {
        list($state, $data) = Storage::instance()->saveShard();
        if ($state) return json($data);
        return json($data, 403);
    });

    /**
     * 完成分片上传
     */
    Route::rule('upload/complateShardUpload', function() {
        list($state, $data) = Storage::instance()->completeShard();
        if ($state) return json(['code' => 1, 'data' => $data]);
        return json(['code' => 0, 'data' => $data]);
    });
}


/**
 * ---------------注册指令
 */
if (class_exists('think\Console')) {
    Console::addDefaultCommands([
        'tpadmin\command\Install', // 安装指令
    ]);
}