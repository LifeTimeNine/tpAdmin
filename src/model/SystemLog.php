<?php

namespace tpadmin\model;

use think\facade\Request;
use tpadmin\Model;

/**
 * 系统管理日志模型
 */
class SystemLog extends Model
{
    protected $deleteTime = false;

    public function user()
    {
        return $this->belongsTo('SystemUser', 'uid')->bind('username');
    }

    public function setIpAttr($value)
    {
        return is_string($value) ? ip2long($value) : $value;
    }
    public function getIpAttr($value)
    {
        return long2ip($value);
    }

    /**
     * 写入日志
     * @param   string  $action     日志行为
     * @param   string  $desc       行为描述
     */
    public static function write($action, $desc = '')
    {
        $request = Request::instance();
        self::create([
            'uid' => $request->isCli() ? 0 : session('sys_user.id'),
            'node' => strtolower($request->module() . '/' . $request->controller() . '/' . $request->action()),
            'action' => $action,
            'desc' => $desc,
            'ip' => $request->isCli() ? '127.0.0.1' : $request->ip(),
        ]);
    }
}