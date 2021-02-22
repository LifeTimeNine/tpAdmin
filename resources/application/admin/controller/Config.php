<?php

namespace app\admin\controller;

use tpadmin\Controller;
use tpadmin\service\Config as ServiceConfig;

/**
 * 系统配置
 * @class   Config
 */
class Config extends Controller
{
    /**
     * 系统参数配置
     * @auth    true
     * @menu    true
     */
    public function info()
    {
        $this->title = '系统参数配置';
        $this->fetch();
    }

    /**
     * 修改系统参数
     * @auth    true
     */
    public function config()
    {
        $this->checkCsrfToken();
        if ($this->request->isGet()) {
            $this->fetch();
        } else {
            $data = $this->request->only(['site_name','app_name', 'app_version', 'miitbeian', 'site_copy', 'site_icon']);
            ServiceConfig::instance()->set($data);
            $this->success();
        }
    }
}
