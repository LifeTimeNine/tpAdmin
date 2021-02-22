<?php

namespace tpadmin\validate;

use think\Validate;

class SystemRule extends Validate
{
    protected $rule = [
        'name' => 'require|length:1,64',
        'desc' => 'length:1,200'
    ];
    protected $message = [
        'name.require' => '名称不能为空',
        'name.length' => '名称超出最大字数限制',
        'desc.length' => '描述超出最大字数限制'
    ];
    protected $scene = [
        'add' => ['name', 'desc'],
        'edit' => ['name', 'desc']
    ];
}