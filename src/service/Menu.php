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
        return self::treeToArr(self::dataToTree($data));
    }

    /**
     * 获取菜单树
     * @return  array
     */
    public function getMenuTree()
    {
        $userRuleNode = Node::instance()->getUserNode();
        $publicNode = array_diff(Node::instance()->getNode(), Node::instance()->getNode(true));
        $userRuleNode = array_merge($userRuleNode, $publicNode);
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

    /**
     * 数据树转数组
     * @param   array   $treeData   数据
     * @param   string  $prefix     前缀
     * @return  array
     */
    public static function treeToArr($treeData, $prefix = '')
    {
        $arr = [];
        foreach ($treeData as $tree) {
            $tmp = $tree;
            unset($tmp['children']);
            $tmp['title'] = $prefix . $tmp['title'];
            $arr[] = $tmp;
            if (!empty($tree['children'])) {
                $arr = array_merge($arr, self::treeToArr($tree['children'], $prefix . '　├　'));
            }
        }
        return $arr;
    }

    /**
     * 数据表数据转数据树
     * @param   array   $data   数据
     * @param   int     $pid    父id
     * @param   int     $tier   层级
     * @return  array
     */
    public static function dataToTree($data, $pid = 0, $tier = 1)
    {
        $tree = [];
        foreach($data as $k => $v) {
            if ($v['pid'] == $pid) {
                $temp = $v;
                $temp['tier'] = $tier;
                $temp['ids'] = join(',', self::getTreeId($data, $v['id']));
                $temp['children'] = self::dataToTree($data, $temp['id'], $tier + 1);
                $tree[] = $temp;
            }
        }
        return $tree;
    }

    /**
     * 获取数据树某个节点自身包括所有子节点的id
     * @param   array   $data   数据
     * @param   int     $id     当前节点id
     * @param   string
     */
    public static function getTreeId($data, $id)
    {
        $ids = [$id];
        foreach ($data as $v) {
            if ($v['pid'] == $id) {
                $ids = array_merge($ids, self::getTreeId($data, $v['id']));
            }
        }
        return $ids;
    }
}