<?php

namespace tpadmin\service;

use think\exception\HttpResponseException;

/**
 * oken服务
 * @class   Token
 */
class Token extends Service
{
    /**
     * 初始化token
     * @param   boolean     $return
     * @return  boolean
     */
    public function init($return = false)
    {
        $this->controller->csrf = true;
        if ($this->app->request->isPost() && !$this->check()) {
            if ($return) return false;
            $this->controller->error('令牌验证失败');
        }
        return true;
    }
    /**
     * 创建csrf token
     * @param   string  $node
     * @return  string
     */
    public function build($node)
    {
        $node = Node::instance()->fullNode($node);
        if ($this->app->session->has("node_{$node}")) {
            return $this->app->session->get("node_{$node}");
        }
        $token = md5(uniqid('csrf'));
        $this->app->session->flash("node_{$node}", $token);
        $this->app->session->set(md5($node), $token);
        return $token;
    }

    /**
     * 验证页面操作token
     * @param   string  $node   节点
     * @return boolean
     */
    public function check($node = null)
    {
        if (is_null($node)) $node = Node::instance()->getCurrentNode();
        $token = $this->app->request->header('user-token-csrf', $this->app->request->param('_token_'));
        $key = md5($node);
        if ($this->app->session->has($key) && $this->app->session->get($key) == $token) {
            $this->app->session->delete($key);
            return true;
        } 
        return false;
    }

    /**
     * 返回视图内容
     * @param string $tpl 模板名称
     * @param array $vars 模板变量
     * @param string $node CSRF授权节点
     */
    public function fetchTemplate($tpl = '', $vars = [], $node = null)
    {
        throw new HttpResponseException(view($tpl, $vars, 200, function ($html) use ($node) {
            return preg_replace_callback('/<\/form>/i', function () use ($node) {
                $csrf = $this->build($node);
                return "<input type='hidden' name='_token_' value='{$csrf}'></form>";
            }, $html);
        }));
    }
}