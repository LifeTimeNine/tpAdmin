<?php

namespace tpadmin\service;

use think\facade\App;
use think\facade\Cache;
use think\facade\Env;
use tpadmin\service\Service;

/**
 * 节点服务类
 * @class Node
 */
class Node extends Service
{
    // 角色节点模型
    protected $ruleNodeModel = 'model\\SystemRuleNode';
    /**
     * 胡忽略的控制器
     * @var array
     */
    protected $ignoreController = ['api'];

    /**
     * 基控制器
     * @var string
     */
    protected $basicController = 'tpadmin\\Controller';

    /**
     * 验证节点授权
     * @param   string  $node   节点
     * @return  boolean
     */
    public function check($node)
    {
        $node = $this->fullNode($node);
        return in_array($node, $this->getUserNode());
    }

    /**
     * 补全节点
     * @param   string  $node
     * @return  string
     */
    public function fullNode($node)
    {
        if (empty($node)) return $this->getCurrentNode();
        if (count(explode('/', $node)) == 1) {
            return "{$this->getCurrentNode('controller')}/{$node}";
        }
        return $node;
    }

    /**
     * 获取用户授权的节点
     * @param   \model\SystemUser   系统用户模型实例
     * @return  array
     */
    public function getUserNode(\tpadmin\model\SystemUser $user = null)
    {
        if (empty($user)) $user = \tpadmin\model\SystemUser::getCurrentUser();
        if ($user->rid == 0) {
            return $this->getNode(true);
        } else {
            $ruleNode = $this->ruleNodeModel::getRuleNode($user->rid);
            return $ruleNode;
        }
    }

    /**
     * 获取当前请求节点
     * @param   string  $type
     * @return  string
     */
    public function getCurrentNode($type = null)
    {
        $request = request();
        if ($type == 'controller') return "{$request->module()}/{$request->controller(true)}";
        return "{$request->module()}/{$request->controller(true)}/{$request->action(true)}";
    }

    /**
     * 获取节点信息
     * @param   string  $node   节点
     * @return  array
     */
    public function getNodeInfo($node = '')
    {
        $nodeTree = $this->getTree();
        if(empty($node)) {
            $request = request();
            $module = $request->module();
            $controller = $request->controller(true);
            $action = $request->action(true);
        } else {
            list($module, $controller, $action) = explode('/', strtr($node, '\\', '/'));
        }
        return empty($nodeTree[$module][$controller]['action'][$action]) ? [] : $nodeTree[$module][$controller]['action'][$action];
    }

    /**
     * 获取节点列表
     * @param   boolean $isAuth 需要验证的节点
     * @param   boolean $isMenu 菜单节点
     * @param   boolean $force  强制刷新
     * @return  array
     */
    public function getNode($isAuth = false, $isMenu = false, $force = false)
    {
        $nodeList = $this->getTree($force);
        $list = [];
        foreach($nodeList as $module => $moduleInfo) {
            foreach($moduleInfo as $controller => $controllerInfo) {
                foreach($controllerInfo['action'] as $action => $actionInfo) {
                    if ($isAuth && !empty($actionInfo['auth'])) {
                        $list[] = "{$module}/{$controller}/{$action}";
                    }
                    if ($isMenu && !empty($actionInfo['menu'])) {
                        $list[] = "{$module}/{$controller}/{$action}";
                    }
                    if (!$isAuth && !$isMenu) {
                        $list[] = "{$module}/{$controller}/{$action}";
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 获取菜单节点
     * @param   boolean $force  强制刷新
     * @return  array
     */
    public function getMenuNode($force = false)
    {
        $nodeList = $this->getTree($force);
        $list = [];
        foreach($nodeList as $module => $moduleInfo) {
            foreach($moduleInfo as $controller => $controllerInfo) {
                foreach($controllerInfo['action'] as $action => $actionInfo) {
                    if (!empty($actionInfo['menu'])) {
                        $list["{$module}/{$controller}/{$action}"] = $actionInfo['title'];
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 获取节点树
     * @param   boolean $force  强制刷新
     * @return  array
     */
    public function getTree($force = false)
    {
        $node = Cache::get('system_node');
        if ($node && !$force) {
            return $node;
        } else {
            $node = $this->find();
            Cache::set('system_node', $node);
            return $node;
        }
    }

    /**
     * 查找节点
     * @return array
     */
    protected function find()
    {
        $data = [];
        $appNamespace = Env::get('APP_NAMESPACE');
        $appPath = App::getAppPath();
        $ignoreAction = get_class_methods($this->basicController);
        foreach ($this->scanDir($appPath, 1) as $module) {
            if (!isset($data[$module])) $data[$module] = [];
            $controllerPath = "{$appPath}/{$module}/controller";
            foreach ($this->scanDir($controllerPath, 2) as $controller) {
                $controllerLower = strtolower($controller);
                if (in_array($controllerLower, $this->ignoreController)) continue;
                $class = new \ReflectionClass("{$appNamespace}\\{$module}\\controller\\{$controller}");
                if ($class->getParentClass() === false || $class->getParentClass()->getName() <> $this->basicController) continue;
                $data[$module][$controllerLower] = array_merge(['action' => []], $this->parseAnnotation($class->getDocComment(), $controller));

                foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $action) {
                    if (in_array($action->getName(), $ignoreAction)) continue;
                    $data[$module][$controllerLower]['action'][$action->getName()] = $this->parseAnnotation($action->getDocComment(), $action->getName(), 2);
                }
            }
        }
        return $data;
    }

    /**
     * 扫描文件夹
     * @param   string  $path   目录地址
     * @param   int     $type   类型 0-所有 1-文件夹 2-文件
     * @param   string  $ext    文件后缀
     * @return  array
     */
    protected function scanDir($path, $type = 0, $ext = 'php')
    {
        if (!is_dir($path)) return [];
        $list = [];
        foreach (scandir($path) as $item) {
            if ($item == '.' || $item == '..') continue;
            if ($type == 0 || ($type == 1 && is_dir("{$path}/{$item}"))) {
                $list[] = $item;
                continue;
            }
            $pathinfo = pathinfo("{$path}/{$item}");
            if ($type == 2 && is_file("{$path}/{$item}") && $pathinfo['extension'] == $ext) {
                $list[] = $pathinfo['filename'];
            }
        }
        return $list;
    }

    /**
     * 解析注释
     * @param   string  $annotation     注释内容
     * @param   string  $default        默认标题
     * @param   int     $type           类型 1-类注释 2-方法注释
     * @return  array
     */
    protected function parseAnnotation($annotation, $default = '', $type = 1)
    {
        $text = strtr($annotation, "\n", ' ');
        $title = preg_replace('/^\/\*\s*\*\s*\*\s*(.*?)\s*\*.*?$/', '$1', $text);
        $data = ['title' => $title ? $title : $default];
        if ($type == 2) {
            // 是否验证节点
            if (preg_match('/@auth\s*true/i', $text)) $data['auth'] = true;
            // 是否在菜单中显示
            if (preg_match('/@menu\s*true/i', $text)) $data['menu'] = true;
            // 是否必须登录
            if (preg_match('/@login\s*false/i', $text)) {
                $data['login'] = false;
            } else {
                $data['login'] = true;
            }
            // 允许的请求类型
            // if (preg_match('/@method\s*((\||get|post|put|delete)*)/i', $text, $method)) {
            //     $data['method'] = explode('|', $method[1]);
            // } else {
            //     $data['method'] = [];
            // }
        }
        return $data;
    }
}