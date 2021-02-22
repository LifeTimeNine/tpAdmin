<?php

namespace tpadmin\service;

use tpadmin\tools\Data;

/**
 * 菜单服务
 * @class   Menu
 */
class Menu extends Service
{
    protected $model = '\\tpadmin\\model\\SystemMenu';

    /**
     * 获取一维数组格式的菜单数据
     * @param   array   $where  额外条件
     * @return  array
     */
    public function getArrData($where = [])
    {
        $data = $this->model::where($where)
            ->order('sort', 'desc')
            ->select()
            ->toArray();
        return Data::treeToArr(Data::dataToTree($data));
    }

    /**
     * 获取菜单树
     * @return  array
     */
    public function getMenuTree()
    {
        $userRuleNode = Node::instance()->getUserRuleNode();
        $data = $this->model::where('status', 1)
            ->order('sort', 'desc')
            ->select()
            ->toArray();
        return self::dataToMenuTree($data, $userRuleNode);
    }

    /**
     * 数据转菜单树
     * @param   array   $data       数据
     * @param   array   $ruleNode   角色授权节点
     * @param   int     $pid        父级ID
     * @return  array
     */
    protected static function dataToMenuTree($data, $ruleNode, $pid = 0)
    {
        $tree = [];
        foreach($data as $v) {
            if ($v['pid'] == $pid) {
                if ($v['url'] <> '#' && !in_array($v['url'], $ruleNode)) continue;
                $tmp = $v;
                $tmp['sub'] = self::dataToMenuTree($data, $ruleNode, $v['id']);
                if ($tmp['url'] == '#' && empty($tmp['sub'])) continue;
                if ($tmp['url'] <> '#') $tmp['url'] = "/{$tmp['url']}";
                $tree[] = $tmp;
            }
        }
        return $tree;
    }
}