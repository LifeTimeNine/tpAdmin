<?php

namespace tpadmin\validate;

use think\Validate;

/**
 * 系统用户验证器
 * @class SystenUser
 */
class SystemUser extends Validate
{
    protected $rule = [
        'username' => 'require|unique:system_user|length:1,32',
        'rid' => 'require|integer',
        'mobile' => 'mobile',
        'email' => 'email',
        'passwod' => 'require',
        'repassword' => 'require|confirm:password'
    ];
    protected $message = [
        'username.require' => '用户名不能为空',
        'username.unique' => '用户名已存在',
        'username.length' => '用户名超出最大字数限制',
        'rid.require' => '请选择角色',
        'rid.integer' => '角色值不正确',
        'mobile.mobile' => '手机号格式不正确',
        'email.email' => '邮箱格式不正确',
        'password.require' => '密码不能为空',
        'repassword.require' => '确认密码不能为空',
        'repassword.confirm' => '两次输入的密码不一致'
    ];
    protected $scene = [
        'add' => ['username', 'rid', 'mobile', 'email'],
        'edit' => ['rid', 'mobile', 'email'],
        'pass' => ['password', 'repassword'],
        'info' => ['mobile', 'email']
    ];
}