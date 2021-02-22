<?php

namespace tpadmin\service;

use think\facade\App;

/**
 * 节点服务类
 */
class Node extends Service
{

    /**
     * 授权节点模型
     * @var string
     */
    protected $nodeModel = '\\tpadmin\\model\\SystemRuleNode';

    /**
     * 忽略的方法
     * @var array
     */
    protected $ignoreMethod = ['__construct', '__debugInfo', 'success', 'error', 'redirect', 'callback', 'fetch'];

    /**
     * 忽略的控制器
     * @var array
     */
    protected $ignoreController = ['api/'];

    /**
     * 节点列表
     * @var array
     */
    protected $node = [];

    /**
     * 获取用户授权节点
     * @return array
     */
    public function getUserRuleNode()
    {
        $user = $this->app->session->get('sys_user');
        $nodeArr = $this->app->session->get('sys_user_rule_node');
        if (empty($user) || $user['rid'] == 0 || empty($nodeArr)) {
            if ($user['rid'] == 0) {
                $nodeArr = array_keys($this->getMethodTree(true));
                $publicNode = array_diff(array_keys($this->getMethodTree(true)), array_keys($this->getMethodTree(true, 'auth')));
            } else {
                $nodeArr = $this->nodeModel::where('rid', $user['rid'])->column('node');
                $publicNode = array_diff(array_keys($this->getMethodTree()), array_keys($this->getMethodTree(false, 'auth')));
            }
            $nodeArr = array_merge($nodeArr, $publicNode);
            $this->app->session->set('sys_user_rule_node', $nodeArr);
            return $nodeArr;
        } else {
            return $nodeArr;
        }
    }

    /**
     * 检查节点是否授权
     * @param   string  $node   节点
     * @return  boolean
     */
    public function check($node)
    {
        $node = $this->fullNode($node);
        return in_array($node, $this->getUserRuleNode());
    }

    /**
     * 获取当前节点
     * @return  string
     */
    public function getCurrentNode()
    {
        return strtolower("{$this->app->request->module()}/{$this->app->request->controller()}/{$this->app->request->action()}");
    }

    /**
     * 获取完整的节点
     * @param   string  $node
     * @return  string
     */
    public function fullNode($node)
    {
        if (empty($node)) return $this->getCurrentNode();
        if (count(explode('/', $node)) == 1) {
            return strtolower("{$this->app->request->module()}/{$this->app->request->controller()}/{$node}");
        }
        return $node;
    }

    /**
     * 获取节点列表
     * @param   boolean     $force      是否强制刷新
     * @return  array
     */
    public function getTree($force = false)
    {
        $data = [];
        if (empty($force)) {
            $data = $this->app->cache->get('system_node');
            if (is_array($data) && count($data) > 0) return $data;
        }
        $appNamespace = $this->app->env->get('APP_NAMESPACE');
        foreach ($this->scanDir(App::getAppPath()) as $file) {
            if (preg_match("|/(\w+)/controller/(.+)\.php$|i", $file, $matches)) {
                list(, $module, $controller) = $matches;
                foreach ($this->ignoreController as $v) if (stripos($controller, $v) === 0) continue 2;
                $class = new \ReflectionClass("{$appNamespace}\\{$module}\\controller\\{$controller}");
                $controller = strtolower($controller);
                if (!isset($data[$module])) $data[$module] = [];
                $data[$module][$controller] = array_merge(['method' => []], $this->parseComment($class->getDocComment(), $controller));

                foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    if (in_array($method->getName(), $this->ignoreMethod)) continue;
                    $data[$module][$controller]['method'][strtolower($method->getName())] = $this->parseComment($method->getDocComment(), $method->getName());
                }
            }
        }
        $this->app->cache->set('system_node', $data);
        $this->node = $data;
        return $data;
    }

    /**
     * 获取控制器节点
     * @param   boolean     $force      是否强制刷新
     * @return array
     */
    public function getControllerTree($force = false)
    {
        $data = [];
        $node = empty($this->node) || $force ? $this->getTree(true) : $this->node;
        foreach ($node as $module => $controllerData) {
            if (!is_array($controllerData)) continue;
            foreach ($controllerData as $controller => $v) {
                $data["{$module}/{$controller}"] = $v['title'];
            }
        }
        return $data;
    }

    /**
     * 获取方法节点
     * @param   boolean     $force      是否强制刷新
     * @param   string      $condition  条件
     * @return  array
     */
    public function getMethodTree($force = false, $condition = '')
    {
        $data = [];
        $node = empty($this->node) || $force ? $this->getTree(true) : $this->node;
        foreach ($node as $module => $controllerData) {
            if (!is_array($controllerData)) continue;
            foreach ($controllerData as $controller => $v) {
                if (!is_array($v['method'])) continue;
                foreach ($v['method'] as $method => $info) {
                    if (!empty($condition)) {
                        if (!empty($info[$condition])) $data["{$module}/{$controller}/{$method}"] = $info['title'];
                    } else {
                        $data["{$module}/{$controller}/{$method}"] = $info['title'];
                    }
                }
            }
        }
        return $data;
    }

    /**
     *  解析节点属性
     * @param   string  $comment    节点内容
     * @param   string  $default    默认标题
     * @return  array
     */
    protected function parseComment($comment, $default = '')
    {
        $text = strtr($comment, "\n", ' ');
        $title = preg_replace('/^\/\*\s*\*\s*\*\s*(.*?)\s*\*.*?$/', '$1', $text);

        $data = ['title' => $title ? $title : $default];
        if (preg_match('/@auth\s*true/i', $text)) $data['auth'] = true;
        if (preg_match('/@menu\s*true/i', $text)) $data['menu'] = true;
        return $data;
    }

    /**
     * 扫描指定目录的文件
     * @param   string  $path   指定目录
     * @param   string  $ext    文件后缀
     * @return  array
     */
    protected function scanDir($path, $ext = 'php')
    {
        $data = [];
        foreach (glob("{$path}*") as $item) {
            if (is_dir($item)) {
                $data = array_merge($data, $this->scanDir("{$item}/"));
            } elseif (is_file($item) && pathinfo($item, PATHINFO_EXTENSION) == $ext) {
                $data[] = strtr($item, '\\', '/');
            }
        }
        return $data;
    }

    /**
     * 驼峰转下划线规则
     * @param string $node 节点名称
     * @return string
     */
    protected function parseString($node)
    {
        if (count($nodes = explode('/', $node)) > 1) {
            $dots = [];
            foreach (explode('.', $nodes[1]) as $dot) {
                $dots[] = trim(preg_replace("/[A-Z]/", "_\\0", $dot), "_");
            }
            $nodes[1] = join('.', $dots);
        }
        return strtolower(join('/', $nodes));
    }
}
