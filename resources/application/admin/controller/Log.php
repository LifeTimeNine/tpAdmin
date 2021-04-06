<?php

namespace app\admin\controller;

use tpadmin\Controller;
use tpadmin\model\SystemUser;
use tpadmin\tools\ip2region\Ip2Region;

/**
 * 系统日志管理
 * @calss   Log
 */
class Log extends Controller
{
    /**
     * 操作模型
     * @var string
     */
    protected $model = '\\tpadmin\\model\\SystemLog';

    /**
     * Ip2Region
     * @var Ip2Region
     */
    protected $ip2region;

    /**
     * 系统操作日志
     * @auth    true
     * @menu    true
     */
    public function index()
    {
        $this->title = '系统操作日志';
        $this->ip2region = new Ip2Region();
        $model = $this->_model();
        $model->with(['user']);
        if ($this->request->has('username', 'get', true)) {
            $uid = SystemUser::whereLike('username', "%{$this->request->param('username')}%")->column('id');
            $model->whereIn('uid', $uid);
        }
        $model->order('id', 'desc');
        $model->_like('node,desc');
        $model->_equal('action');
        $model->_timestampBetween('create_time');
        $model->_page();
    }

    protected function _page_filter(&$data)
    {
        $location = $this->ip2region->btreeSearch($data['ip']);
        $location = isset($location['region']) ? $location['region'] : '';
        $data['location'] = str_replace(['内网IP', '0', '|'], '', $location);
    }

    /**
     * 删除系统日志
     * @auth    true
     */
    public function remove()
    {
        $this->checkCsrfToken();
        $this->_delete('', false);
    }

    /**
     * 清理日志
     * @auth    true
     */
    public function clear()
    {
        $this->checkCsrfToken();
        $this->model::whereRaw('1=1')->delete();
        $this->success();
    }
}