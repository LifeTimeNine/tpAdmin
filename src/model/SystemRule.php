<?php

namespace tpadmin\model;

use tpadmin\Model;

/**
 * 系统角色模型
 * @class   SystemRule
 */
class SystemRule extends Model
{
    public function node()
    {
        return $this->hasMany('SystemRuleNode', 'rid', 'id');
    }

    public static $status = [
        1 => '使用中',
        2 => '已禁用'
    ];
}