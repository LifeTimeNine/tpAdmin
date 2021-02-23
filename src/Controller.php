<?php

namespace tpadmin;

use think\App;
use think\Container;
use think\exception\HttpResponseException;
use tpadmin\model\SystemUser;
use tpadmin\service\Model;
use tpadmin\service\Token;

/**
 * 控制器基类
 */
class Controller extends \stdClass
{
    /**
     * 当前请求实例
     * @var App
     */
    protected $app;

    /**
     * 当前请求对象
     * @var \think\Request
     */
    protected $request;

    /**
     * 当前操作模型
     * @var string
     */
    protected $model = '';

    /**
     * 当前操作验证器
     * @var string
     */
    protected $validate = '';

    /**
     * 视图类实例
     * @var \think\View
     */
    protected $view;

    /**
     * CSRF 状态
     * @var boolean
     */
    public $csrf = false;

    /**
     * 当前登录用户
     * @var SystemUser
     */
    public $user;

    /**
     * 构造函数
     * @param   App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->view = $app->view;
        // 控制器注入容器
        Container::set('tpadmin\Controller', $this);

        $this->initialize();
    }

    /**
     * 控制器初始化
     */
    protected function initialize()
    {
        if (strtolower($this->request->controller()) <> 'login') {
            if (!SystemUser::isLogin()) {
                $this->redirect('@admin/login/index');
            }
            $this->user = SystemUser::getCurrentUser();
            // if (!$this->request->isGet() && $this->user->last_login_ip <> $this->request->ip()) {
            //     SystemUser::loginout();
            //     $this->error('此账号已在其他地点被登录，被迫下线', url('admin/login/index'));
            // }
        }
    }

    /**
     * 返回成功的操作
     * @param   string  $info   消息内容
     * @param   array   $data   消息数据
     */
    public function success($info = '操作成功', $data = [])
    {
        throw new HttpResponseException(json([
            'code' => 1,
            'info' => $info,
            'data' => $data
        ]));
    }

    /**
     * 返回失败的操作
     * @param   string  $info   消息内容
     * @param   array   $data   消息数据
     */
    public function error($info = '操作失败', $data = [])
    {
        throw new HttpResponseException(json([
            'code' => 0,
            'info' => $info,
            'data' => $data
        ]));
    }

    /**
     * 返回视图
     * @param   string  $tpl    模板名称
     * @param   array   $vars   模板变量
     * @param   string  $node   当前节点
     */
    public function fetch($tpl ='', $vars = [], $node = null)
    {
        foreach($this as $k => $v) $vars[$k] = $v;
        if ($this->csrf) {
            Token::instance()->fetchTemplate($tpl, $vars, $node);
        } else {
            throw new HttpResponseException(view($tpl, $vars));
        }
    }

    /**
     * URL重定向
     * @param   string  $url    跳转连接
     * @param   array   $vars   跳转参数
     * @param   int     $code   跳转代码
     */
    public function redirect($url, $vars = [], $code = 301)
    {
        throw new HttpResponseException(redirect($url, $vars, $code));
    }

    /**
     * 数据回调处理
     * @param   string  $name   回调方法名称
     * @param   mixed   $param1 回调引用参数1
     * @param   mixed   $param2 回调引用参数2
     * @return  boolean
     */
    public function callback($name, &$param1 = null, &$param2 = null)
    {
        foreach(["_{$this->request->action()}{$name}", $name] as $method) {
            if (method_exists($this, $method)) {
                return $this->$method($param1, $param2);
            }
        }
        return true;
    }

    /**
     * 快捷模型初始化
     * @param   string  $model  操作模型名称
     * @return  Model
     */
    protected function _model($model = '')
    {
        if (empty($model)) $model = $this->model;
        return Model::instance()->setModel($model);
    }
    
    /**
     * 检查操作令牌
     * @param   boolean $return 检查失败时是否直接返回失败的操作
     * @return  boolean
     */
    protected function checkCsrfToken($return = true)
    {
        Token::instance()->init($return);
    }

    /**
     * 快捷更新
     * @param   string  $model  操作模型名称
     * @param   array   $data   更新的数据
     * @param   string  $field  更新主键字段
     */
    protected function _save($model = '', $data = [], $field = '')
    {
        if (empty($model)) $model = $this->model;
        Model::instance()->_save($model, $data, $field);
    }

    /**
     * 快捷删除
     * @param   string  $model      操作模型名称
     * @param   boolean $softDelete 是否软删除
     * @param   string  $field      主键字段
     */
    protected function _delete($model = '', $softDelete = true, $field = '')
    {
        if (empty($model)) $model = $this->model;
        Model::instance()->_delete($model, $softDelete, $field);
    }

    /**
     * 快捷表单
     * @param   string  $model      操作模型名称
     * @param   string  $template   模板名称
     * @param   string  $validate   验证器名称[名称.场景][.场景(需要在控制器中设置属性validate)]
     * @param   string  $field      主键
     */
    protected function _form($model = '', $template = '', $validate = '', $field = '')
    {
        if (empty($model)) $model = $this->model;
        if (!empty($validate) && strpos($validate, '.') == 0) {
            $validate = $this->validate . $validate;
        } elseif (empty($validate)) {
            $validate = $this->validate;
        }
        Model::instance()->_form($model, $template, $validate, $field);
    }
}