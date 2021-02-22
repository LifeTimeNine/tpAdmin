<?php

namespace tpadmin\model;

use think\facade\Request;
use think\facade\Session;
use tpadmin\Model;

/**
 * 系统用户模型
 */
class SystemUser extends Model
{
    public function setLastLoginIpAttr($value)
    {
        return is_string($value) ? ip2long($value) : $value;
    }
    public function getLastLoginIpAttr($value)
    {
        return empty($value) ? '-' : long2ip($value);
    }
    public function rule()
    {
        return $this->belongsTo('SystemRule', 'rid');
    }
    /**
     * 登录
     * @param   string  $username   用户账户
     * @param   string  $password   用户密码
     * @param   string  $uniqid     图形验证码标识
     * @return  string|boolean
     */
    public static function login($username, $password, $uniqid) {
        $user = self::getByUsername($username);
        if (empty($user) || md5("{$user->password}{$uniqid}") <> $password || $user->status <> 1) {
            return '用户名或密码错误!';
        }
        $user->isUpdate()->save([
            'last_login_time' => time(),
            'last_login_ip' => Request::ip(),
            'login_num' => ['inc', 1]
        ]);
        Session::set('sys_user', $user);
        SystemLog::write('系统管理', '用户登录系统');
        return true;
    }
    /**
     * 判断是否是登录的状态
     * @return  boolean
     */
    public static function isLogin()
    {
        return Session::has('sys_user');
    }
    /**
     * 获取当前登录的用户
     * @return  Model
     */
    public static function getCurrentUser()
    {
        return self::get(Session::get('sys_user.id'));
    } 
    /**
     * 退出登录
     */
    public static function loginout()
    {
        Session::clear();
    }
}