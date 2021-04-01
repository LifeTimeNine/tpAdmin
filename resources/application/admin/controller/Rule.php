<?php

namespace app\admin\controller;

use think\Db;
use tpadmin\Controller;
use tpadmin\model\SystemLog;
use tpadmin\model\SystemRuleNode;
use tpadmin\service\Node;

/**
 * 角色管理
 * @class   Rule
 */
class Rule extends Controller
{
    /**
     * 定义操作模型
     * @var string
     */
    protected $model = '\\tpadmin\\model\\SystemRule';

    /**
     * 定义验证器
     * @var string
     */
    protected $validate = '\\tpadmin\\validate\\SystemRule';

    /**
     * 角色列表
     * @auth    true
     * @menu    true
     */
    public function index()
    {
        $this->title = '角色列表';
        $model = $this->_model();
        $model->_like('name@name,desc');
        $model->_eq('status');
        $model->_timestampBetween('create_time');
        $model->_page();
    }

    /**
     * 禁用角色
     * @auth    true
     */
    public function forbid()
    {
        $this->checkCsrfToken();
        $this->_save('', ['status' => 2]);
    }
    /**
     * 启用角色
     * @auth    true
     */
    public function resume()
    {
        $this->checkCsrfToken();
        $this->_save('', ['status' => 1]);
    }
    /**
     * 删除角色
     * @auth    true
     */
    public function remove()
    {
        $this->checkCsrfToken();
        $this->_delete();
    }

    /**
     * 添加角色
     * @auth    true
     */
    public function add()
    {
        $this->checkCsrfToken();
        $this->_form('', 'form', ".add");
    }

    /**
     * 编辑角色
     * @auth    true
     */
    public function edit()
    {
        $this->checkCsrfToken();
        $this->_form('', 'form', ".edit");
    }

    /**
     * 刷新权限
     * @auth    true
     */
    public function refresh()
    {
        Node::instance()->getTree(true);
        $this->success();
    }

    /**
     * 授权
     * @auth    true
     */
    public function apply()
    {
        if ($this->request->isGet()) {
            $this->title = '授权';
            $this->id = $this->request->param('id');
            $this->fetch();
        } else {
            $id = $this->request->param('id');
            $data = $this->request->param('data', []);
            if (!is_array($data)) $this->error('错误的数据格式');
            foreach($data as &$node) $node = ['rid' => $id, 'node' => $node];
            // 启动事务
            Db::startTrans();
            try {
                SystemRuleNode::where('rid', $id)->delete();
                (new SystemRuleNode)->saveAll($data);
                SystemLog::write('系统管理', '角色授权');
                // 提交事务
               Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
               Db::rollback();
               $this->error("操作失败: {$e->getMessage()}");
            }
            $this->success();
        }
    }

    /**
     * 获取节点数据
     */
    public function getNode()
    {
        $ruleNode = SystemRuleNode::where('rid', $this->request->param('id'))->column('node');
        $data = Node::instance()->getTree();
        $node = [];
        foreach ($data as $module => $controllerArr)
        {
            if (!$controllerArr) continue;
            $moduleNode = [
                'name' => $module,
                'open' => true,
                'node' => $module,
                'checked' => in_array($module, $ruleNode),
                'children' => []
            ];
            foreach ($controllerArr as $controller => $controllerData)
            {
                if (!$controllerData['action']) continue;
                $controllerNode = [
                    'name' => $controllerData['title'],
                    'node' => "{$module}/{$controller}",
                    'open' => true,
                    'checked' => in_array("{$module}/{$controller}", $ruleNode),
                    'children' => []
                ];
                foreach ($controllerData['action'] as $action => $actionData) {
                    if (!empty($actionData['auth'])) {
                        $controllerNode['children'][] = [
                            'name' => $actionData['title'],
                            'checked' => in_array("{$module}/{$controller}/{$action}", $ruleNode),
                            'node' => "{$module}/{$controller}/{$action}",
                        ];
                    }
                }
                if (count($controllerNode['children']) > 0) {
                    $moduleNode['children'][] = $controllerNode;
                }
            }
            if (count($moduleNode['children']) > 0) {
                $node[] = $moduleNode;
            }
        }
        $this->success('获取成功', $node);
    }

    protected function _save_extra($data)
    {
        SystemLog::write('系统管理', ($data['status'] == 1 ? '启用' : '禁用').'角色');
    }

    protected function _delete_extra()
    {
        SystemLog::write('系统管理', '删除角色');
    }

    protected function _form_filter()
    {
        if ($this->request->isPost()) {
            SystemLog::write('系统管理', '更新角色');
        }
    }
}