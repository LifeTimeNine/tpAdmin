<?php

namespace tpadmin\model;

use think\facade\Cache;
use tpadmin\Model;

/**
 * 系统角色节点模型
 * @class   SystemRuleNode
 */
class SystemRuleNode extends Model
{
    protected $autoWriteTimestamp = true;
    protected $deleteTime = false;

    /**
     * 获取某个角色授权节点列表
     * @param   int     $rid    角色id
     * @param   boolean $force  强制刷新
     * @return  array
     */
    static public function getRuleNode($rid, $force = false)
    {
        $ruleNode = Cache::get("rule_node_rid_{$rid}");
        if (empty($ruleNode) && $force) {
            $ruleNode = $this->ruleNodeModel::where('rid', $rid)->column('node');
            Cache::set("rule_node_rid_{$rid}", $ruleNode);
        }
        return $ruleNode;
    }

    /**
     * 缓存角色节点列表
     * @param   int     $rid    角色id
     * @param   array   $node   节点列表
     */
    static public function cacheRuleNode($rid, $node)
    {
        Cache::set("rule_node_rid_{$rid}", $node);
    }
}