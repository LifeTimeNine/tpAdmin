<?php

namespace tpadmin\tools;

/**
 * 数据工具类
 * @package tpadmin\tools
 */
class Data
{
    /**
     * 生成唯一日期编码
     * @param   int     $length     编码长度(最小14)
     * @return  string
     */
    public static function uniqidDateCode($length = 14)
    {
        if ($length < 14) $length = 14;
        $str = date('Ymd') . (date('H') + date('i')) . date('s');
        while(strlen($str) < $length) $str .= rand(0, 9);
        return $str;
    }

    /**
     * 唯一数字编码
     * @param integer $length
     * @return string
     */
    public static function uniqidNumberCode($length = 10)
    {
        $time = time() . '';
        if ($length < 10) $length = 10;
        $string = ($time[0] + $time[1]) . substr($time, 2) . rand(0, 9);
        while (strlen($string) < $length) $string .= rand(0, 9);
        return $string;
    }
}