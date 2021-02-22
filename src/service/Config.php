<?php

namespace tpadmin\service;

use think\facade\Cache;

/**
 * 系统配置服务
 * @class Config
 */
class Config
{
    /**
     * 系统配置模型
     * @var string
     */
    protected $model = '\\tpadmin\\model\\SystemConfig';

    /**
     * 静态实例化对象
     * @return  static
     */
    public static function instance()
    {
        return new static;
    }

    /**
     * 获取配置
     * @param   string  $name   配置名称
     * @param   boolean $force  强制获取最新配置
     * @retrn   mxied
     */
    public function get($name = null, $force = false)
    {
        $data = Cache::get("sys_config");
        if (!empty($force) || empty($data)) {
            $data = $this->parse($this->model::all());
        }
        return empty($name) ? $data : (empty($data[$name]) ? null : $data[$name]);
    }

    /**
     * 设置配置
     * @param   string|array    $name   配置名称|配置集
     * @param   mixed           $value  配置值
     */
    public function set($name, $value = null)
    {
        if (!is_array($name)) {
            $this->model::update(['value' => $value], ['name', $name]);
        } else {
            (new $this->model)->saveAll($this->theParse($name));
        }
        $this->refresh();
    }

    /**
     * 刷新配置
     */
    protected function refresh()
    {
        $data = $this->parse($this->model::all());
        Cache::set('sys_config', $data);
    }

    /**
     * 解析配置数据
     * @param    array   $data  数据库数据集
     * @return  array
     */
    protected function parse($data)
    {
        $config = [];
        foreach($data as $item) {
            $config[$item['name']] = $item['value'];
        }
        return $config;
    }

    /**
     * 获取数据集中是否存在某个配置项
     * @param   array   $data
     * @param   string  $name
     * @return  int
     */
    protected function issetItem($data, $name)
    {
        foreach($data as $v) {
            if ($v['name'] == $name) {
                return $v['id'];
            }
        }
        return 0;
    }

    /**
     * 反解析配置数据
     * @param   array   $data   配置数据
     * @return  array
     */
    protected function theParse($data)
    {
        $oldConfig = $this->model::all();
        $config = [];
        foreach($data as $k => $v)
        {
            $tmp = ['name' => $k, 'value' => $v];
            $id = $this->issetItem($oldConfig, $k);
            if ($id !== 0) {
                $tmp['id'] = $id;
            }
            $config[] = $tmp;
        }
        return $config;
    }
}