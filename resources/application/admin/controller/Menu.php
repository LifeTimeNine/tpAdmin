<?php

namespace app\admin\controller;

use tpadmin\Controller;
use tpadmin\model\SystemLog;
use tpadmin\service\Menu as ServiceMenu;
use tpadmin\service\Node;

/**
 * 系统菜单管理
 * @class   Menu
 */
class Menu extends Controller
{
    /**
     * 当前操作模型名称
     * @var string
     */
    protected $model = "\\tpadmin\model\SystemMenu";

    /**
     * 系统菜单管理
     * @auth    true
     * @menu    true
     */
    public function index()
    {
        $this->title = '系统菜单管理';
        $this->list = ServiceMenu::instance()->getArrData();
        $this->fetch();
    }

    /**
     * 禁用菜单
     * @auth    true
     */
    public function forbid()
    {
        $this->checkCsrfToken();
        $this->_save('', ['status' => 2]);
    }

    /**
     * 新增菜单
     * @auth    true
     */
    public function add()
    {
        $this->checkCsrfToken();
        $this->_form('', 'form');
    }

    /**
     * 编辑菜单
     * @auth    true
     */
    public function edit()
    {
        $this->checkCsrfToken();
        $this->_form('', 'form');
    }

    protected function _form_filter()
    {
        if ($this->request->isGet()) {
            $this->pid = $this->request->param('pid');
            $this->menus = ServiceMenu::instance()->getArrData();
            $this->nodes = Node::instance()->getNode(false, true);
        } else {
            SystemLog::write('系统管理', '更新菜单');
        }
    }

    /**
     * 启用菜单
     * @auth    true
     */
    public function resume()
    {
        $this->checkCsrfToken();
        $this->_save('', ['status' => 1]);
    }

    /**
     * 删除菜单
     * @auth    true
     */
    public function remove()
    {
        $this->checkCsrfToken();
        $this->_delete();
    }

    /**
     * 刷新菜单
     * @auth    true
     */
    public function refresh()
    {
        $this->checkCsrfToken();
        Node::instance()->getTree(true);
        $this->success('刷新成功');
    }

    protected function _save_extra($data)
    {
        SystemLog::write('系统管理', ($data['status'] == 1 ? '启用' : '禁用') . '菜单');
    }

    protected function _delete_extra($data)
    {
        SystemLog::write('系统管理', '删除菜单');
    }
}