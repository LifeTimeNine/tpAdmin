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
        return Data::dataToMenuTree($data, $userRuleNode);
    }
}