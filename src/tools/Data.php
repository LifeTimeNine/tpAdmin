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
}