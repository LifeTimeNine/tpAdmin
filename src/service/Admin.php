<?php

namespace tpadmin\service;

/**
 * 系统权限服务
 */
class Admin extends Service
{
    /**
     * 判断是否已经登录
     * @return  boolean
     */
    public function isLogin()
    {
        return session('?sys_user');
    }
}