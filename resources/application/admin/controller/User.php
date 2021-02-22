<?php

namespace app\admin\controller;

use tpadmin\Controller;
use tpadmin\model\SystemLog;
use tpadmin\model\SystemRule;

/**
 * 系统用户管理
 * @class User
 */
class User extends Controller
{
    protected $model = '\\tpadmin\\model\\SystemUser';

    protected $validate = '\\tpadmin\\validate\\SystemUser';

    /**
     * 系统用户管理
     * @auth    true
     * @menu    true
     */
    public function index()
    {
        $this->title = '系统用户管理';
        $model = $this->_model();
        $model->with(['rule']);
        $model->where('rid', '<>', 0);
        $model->_like('username,mobile');
        $model->_eq('status');
        $model->_timestampBetween('last_login_time');
        $model->_page();
    }

    /**
     * 新增系统用户
     * @auth    true
     */
    public function add()
    {
        $this->checkCsrfToken();
        $this->_form('', 'form', '.add');
    }

    /**
     * 修改系统用户
     * @auth    true
     */
    public function edit()
    {
        $this->checkCsrfToken();
        $this->_form('', 'form', '.edit');
    }

    /**
     * 修改密码
     * @auth    true
     */
    public function pass()
    {
        if ($this->request->isGet()) {
            $this->fetch();
        } else {
            if ($this->request->param('password') <> $this->request->param('repassword')) {
                $this->error('两次输入的密码不一致');
            }
            if (md5($this->request->param('oldpassword')) <> $this->app->session->get('sys_user.password')) {
                $this->error('密码验证失败');
            }
            $this->model::update(['password' => md5($this->request->param('password'))], ['id' => $this->app->session->get('sys_user.id')]);
            $this->model::loginout();
            $this->success('修改成功，请重新登录', url('@admin/login/index'));
        }
    }

    /**
     * 重置密码
     * @auth    true
     */
    public function resetPwd()
    {
        if ($this->request->isGet()) {
            $this->fetch();
        } else {
            if (md5($this->request->param('password')) <> $this->app->session->get('sys_user.password')) {
                $this->error('密码验证失败');
            }
            $this->model::update(['password' => md5('123456')], ['id' => $this->request->param('id')]);
            $this->success();
        }
    }

    protected function _form_filter(&$data)
    {
        if ($this->request->isGet()) {
            $this->rules = SystemRule::where('status', 1)->select();
        } else {
            if (!$this->request->has('id', 'post', true)) {
                $data['password'] = md5('123456');
            }
            SystemLog::write('系统管理', '更新系统用户');
        }
    }

    /**
     * 禁用用户
     * @auth    true
     */
    public function forbid()
    {
        $this->checkCsrfToken();
        $this->_save('', ['status' => 2]);
    }
    /**
     * 启用用户
     * @auth    true
     */
    public function resume()
    {
        $this->checkCsrfToken();
        $this->_save('', ['status' => 1]);
    }
    /**
     * 删除用户
     * @auth    true
     */
    public function remove()
    {
        $this->checkCsrfToken();
        $this->_delete();
    }

    /**
     * 修改信息
     */
    public function info()
    {
        $this->_form('', 'info', '.info');
    }

    protected function _info_form_filter()
    {
        if ($this->request->isPost()) {
            $this->app->session->set('sys_user', $this->model::get($this->app->session->get('sys_user.id')));
        }
    }

    protected function _save_extra($data)
    {
        SystemLog::write('系统管理', ($data['status'] == 1 ? '启用' : '禁用').'系统用户');
    }
    protected function _delete_extra()
    {
        SystemLog::write('系统管理', '删除系统用户');
    }
}