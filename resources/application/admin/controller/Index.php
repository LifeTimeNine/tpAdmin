<?php

namespace app\admin\controller;

use think\Db;
use tpadmin\Controller;
use tpadmin\service\Menu;

/**
 * 首页
 * @Class   Index
 * @package app\admin\controller
 */
class Index extends Controller
{
    protected $model = '\\tpadmin\\model\\SystemUser';
    /**
     * 首页
     * @menu    true
     */
    public function index()
    {
        $this->menu = Menu::instance()->getMenuTree();
        $this->fetch('/main');
    }
    /**
     * 首页
     */
    public function main()
    {
        $this->think_ver = \think\App::VERSION;
        $this->mysql_ver = Db::query('select version() as ver')[0]['ver'];
        $this->fetch('index');
    }
}