<?php

namespace tpadmin\service;

use think\App;
use think\Container;
use tpadmin\Controller;

/**
 * 服务基类
 * @package tpadmin\service
 */
abstract class Service
{
    /**
     * App
     * @var App
     */
    protected $app;

    /**
     * Controller
     * @var Controller
     */
    protected $controller;
    /**
     * 构造函数
     */
    public function __construct(App $app = null, Controller $controller = null)
    {
        $this->app = $app;
        $this->controller = $controller;
    }
    /**
     * 静态实例化对象
     * @return  static
     */
    public static function instance()
    {
        return Container::getInstance()->invokeClass(static::class, func_get_args());
    }
}
