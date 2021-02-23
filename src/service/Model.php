<?php

namespace tpadmin\service;

use think\Db;
use think\facade\Request;

/**
 * 快捷模型服务
 * @class   Model
 */
class Model extends Service
{
    /**
     * 模型对象
     * @var \think\Model
     */
    protected $modelObj;

    /**
     * 验证模型是否定义
     * @param   string  $model  模型名称
     */
    protected function checkDefine($model)
    {
        if (!class_exists($model)) throw new \Exception("Model [{$model}] does not exist");
    }

    /**
     * 设置模型
     * $param   string  $model  模型名称
     * @retrn   $this
     */
    public function setModel($model)
    {
        if (!empty($this->modelObj)) throw new \Exception("The model cannot be set repeatedly");
        $this->checkDefine($model);
        $this->modelObj = (new $model())->db();
        return $this;
    }

    /**
     * @param string $name 调用方法名称
     * @param array $args 调用参数内容
     * @return $this
     */
    public function __call($name, $args)
    {
        if (is_callable($callable = [$this->modelObj, $name])) {
            call_user_func_array($callable, $args);
        }
        return $this;
    }

    /**
     * 整理查询条件
     * @param   array|string    $fields     查询的字段
     * @param   string          $type       输入类型
     * @param   string          $alias      别名分隔符
     * @param   callable        $callable   查询操作方法
     */
    protected function parseWhere($fields, $type = 'param', $alias = '@', $callable)
    {
        $data = Request::$type();
        foreach (is_array($fields) ? $fields : explode(',', $fields) as $field) {
            $dk = $field;
            $qk = $field;
            if (stripos($field, $alias) !== false) {
                list($dk, $qk) = explode($alias, $field);
            }
            if (isset($data[$qk]) && $data[$qk] !== '') {
                if (is_callable($callable)) {
                    call_user_func($callable, $dk, $data[$qk]);
                }
            }
        }
    }

    /**
     * 快捷Like查询
     * @param   array|string    $fields     查询的字段
     * @param   string          $type       输入类型
     * @param   string          $alias      别名分隔符
     * @return  $this
     */
    public function _like($fields, $type = 'param', $alias = '@')
    {
        $this->parseWhere($fields, $type, $alias, function ($dk, $value) {
            $this->modelObj->whereLike($dk, "%{$value}%");
        });
        return $this;
    }

    /**
     * 快捷NotLike查询
     * @param   array|string    $fields     查询的字段
     * @param   string          $type       输入类型
     * @param   string          $alias      别名分隔符
     * @return  $this
     */
    public function _notLike($fields, $type = 'param', $alias = '@')
    {
        $this->parseWhere($fields, $type, $alias, function ($dk, $value) {
            $this->modelObj->whereNotLike($dk, "%{$value}%");
        });
        return $this;
    }

    /**
     * 快捷Eq查询
     * @param   array|string    $fields     查询的字段
     * @param   string          $type       输入类型
     * @param   string          $alias      别名分隔符
     * @return  $this
     */
    public function _eq($fields, $type = 'param', $alias = '@')
    {
        $this->parseWhere($fields, $type, $alias, function ($dk, $value) {
            $this->modelObj->where($dk, $value);
        });
        return $this;
    }

    /**
     * 快捷Neq查询
     * @param   array|string    $fields     查询的字段
     * @param   string          $type       输入类型
     * @param   string          $alias      别名分隔符
     * @return  $this
     */
    public function _notEq($fields, $type = 'param', $alias = '@')
    {
        $this->parseWhere($fields, $type, $alias, function ($dk, $value) {
            $this->modelObj->where($dk, '<>', $value);
        });
        return $this;
    }

    /**
     * 快捷In查询
     * @param   array|string    $fields     查询的字段
     * @param   string          $split      输入分隔符
     * @param   string          $type       输入类型
     * @param   string          $alias      别名分隔符
     * @return  $this
     */
    public function _in($fields, $split = ',', $type = 'param', $alias = '@')
    {
        $this->parseWhere($fields, $type, $alias, function ($dk, $value) use ($split) {
            $this->modelObj->whereIn($dk, explode($split, $value));
        });
        return $this;
    }

    /**
     * 快捷NotIn查询
     * @param   array|string    $fields     查询的字段
     * @param   string          $split      输入分隔符
     * @param   string          $type       输入类型
     * @param   string          $alias      别名分隔符
     * @return  $this
     */
    public function _notIn($fields, $split = ',', $type = 'param', $alias = '@')
    {
        $this->parseWhere($fields, $type, $alias, function ($dk, $value) use ($split) {
            $this->modelObj->whereNotIn($dk, explode($split, $value));
        });
        return $this;
    }

    /**
     * 快捷Between查询
     * @param   array|string    $fields     查询的字段
     * @param   string          $split      输入分隔符
     * @param   string          $type       输入类型
     * @param   string          $alias      别名分隔符
     * @return  $this
     */
    public function _between($fields, $split = '-', $type = 'param', $alias = '@')
    {
        $this->parseWhere($fields, $type, $alias, function ($dk, $value) use ($split) {
            $this->modelObj->whereBetween($dk, explode($split, $value));
        });
        return $this;
    }

    /**
     * 快捷NotBetween查询
     * @param   array|string    $fields     查询的字段
     * @param   string          $split      输入分隔符
     * @param   string          $type       输入类型
     * @param   string          $alias      别名分隔符
     * @return  $this
     */
    public function _notBetween($fields, $split = '-', $type = 'param', $alias = '@')
    {
        $this->parseWhere($fields, $type, $alias, function ($dk, $value) use ($split) {
            $this->modelObj->whereNotBetween($dk, explode($split, $value));
        });
        return $this;
    }

    /**
     * 快捷日期区间查询
     * @param   array|string    $fields     查询的字段
     * @param   string          $split      输入分隔符
     * @param   string          $type       输入类型
     * @param   string          $alias      别名分隔符
     * @return  $this
     */
    public function _dateBetween($fields, $split = ' - ', $type = 'param', $alias = '@')
    {
        $this->parseWhere($fields, $type, $alias, function ($dk, $value) use ($split) {
            list($before, $after) = explode($split, $value);
            $this->modelObj->whereTime($dk, ["{$before} 00:00:00", "{$after} 23:59:59"]);
        });
        return $this;
    }

    /**
     * 快捷时间戳区间查询
     * @param   array|string    $fields     查询的字段
     * @param   string          $split      输入分隔符
     * @param   string          $type       输入类型
     * @param   string          $alias      别名分隔符
     * @return  $this
     */
    public function _timestampBetween($fields, $split = ' - ', $type = 'param', $alias = '@')
    {
        $this->parseWhere($fields, $type, $alias, function ($dk, $value) use ($split) {
            list($before, $after) = explode($split, $value);
            $this->modelObj->whereTime($dk, [strtotime("{$before} 00:00:00"), strtotime("{$after} 23:59:59")]);
        });
        return $this;
    }

    /**
     * 快捷Select
     * @return  \think\Collection|array|\PDOStatement|string
     */
    public function _select()
    {
        return $this->modelObj->select();
    }

    /**
     * 快捷分页
     * @param   string  $template   模板名称
     * @param   int     $listRows   每页数量
     * @param   bool    $simple     是否简洁模式
     * @return  mixed
     */
    public function _page($template = '', $listRows = 10, $simple = false)
    {
        if ($this->app->request->has('listRows', 'param', true)) {
            $listRows = $this->app->request->param('listRows');
        }
        $list = $this->modelObj->paginate($listRows, $simple, ['type' => "\\tpadmin\\driver\\page\\Page"])->each(function ($item, $key) {
            $this->controller->callback('_page_filter', $item, $key);
        });
        $this->controller->fetch($template, [
            'list' => $list,
            'page' => $list->render()
        ]);
    }

    /**
     * 快捷更新
     * @param   string  $model  操作模型名称
     * @param   array   $data   更新的数据
     * @param   string  $field  更新主键字段
     */
    public function _save($model = '', $data = [], $field = '')
    {

        $this->checkDefine($model);
        $pk = empty($field) ? (new $model)->getPk() : $field;
        $pkVal = explode(',', $this->app->request->param($pk));
        if (count($pkVal) > 1) {
            $where = [[$pk , 'in', $pkVal]];
        } else {
            $where = [$pk => $pkVal];
        }
        $returnMsg = '';
        // 启动事务
        Db::startTrans();
        try {
            $result = $model::update($data, $where);
            $callbackResult = $this->controller->callback('_save_extra', $data, $where);
            if (is_string($callbackResult) || $callbackResult === false) {
                throw new \Exception($callbackResult);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $result = false;
            $returnMsg = $e->getMessage();
        }
        // 响应处理
        if ($result !== false) {
            $this->controller->success();
        } else {
            $this->controller->error("操作失败" . (empty($returnMsg) ? $returnMsg : ": {$returnMsg}"));
        }
    }

    /**
     * 快捷删除
     * @param   string  $model      操作模型名称
     * @param   boolean $softDelete 是否软删除
     * @param   string  $field      主键字段
     */
    public function _delete($model = '', $softDelete = true, $field = '')
    {
        $this->checkDefine($model);
        $pk = empty($field) ? (new $model)->getPk() : $field;
        $where = $this->app->request->param($pk);
        $returnMsg = '';
        // 启动事务
        Db::startTrans();
        try {
            if ($softDelete) {
                $result = $model::destroy($where);
            } else {
                $result = $model::destroy($where, true);
            }
            $callbackResult = $this->controller->callback('_delete_extra', $where);
            if (is_string($callbackResult) || $callbackResult === false) {
                throw new \Exception($callbackResult);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $result = false;
            $returnMsg = $e->getMessage();
        }
        // 响应处理
        if ($result !== false) {
            $this->controller->success();
        } else {
            $this->controller->error("操作失败" . (empty($returnMsg) ? $returnMsg : ": {$returnMsg}"));
        }
    }

    /**
     * 快捷表单
     * @param   string  $model      操作模型名称
     * @param   string  $template   模板名称
     * @param   string  $validate   验证器名称(名称.场景)
     * @param   string  $field      主键
     */
    public function _form($model = '', $template = '', $validate = '', $field = '')
    {
        $this->checkDefine($model);
        $pk = empty($field) ? (new $model)->getPk() : $field;
        $pkVal = $this->app->request->param($pk);

        if ($this->app->request->isGet()) {
            $data = $model::where($pk, $this->app->request->param($pk))->find();
            $this->controller->callback('_form_filter', $data);
            $this->controller->fetch($template, ['vo' => $data]);
        } elseif ($this->app->request->isPost()) {
            $data = $this->app->request->post();
            if (!empty($validate)) {
                if (strpos($validate, '.') !== false) {
                    list($validate, $scene) = explode('.', $validate);
                }
                $vali = new $validate;
                $valiRes = empty($scene) ? $vali->check($data) : $vali->scene($scene)->check($data);
                if ($valiRes !== true) {
                    $this->controller->error($vali->getError());
                }
            }
            $result = true;
            $returnMsg = '';
            // 启动事务
            Db::startTrans();
            try {
                $callbackResult = $this->controller->callback('_form_filter', $data);
                if (is_string($callbackResult) || $callbackResult === false) {
                    throw new \Exception($callbackResult);
                }
                if (empty($pkVal)) {
                    $model::create($data);
                } else {
                    unset($data[$pk]);
                    $model::update($data, [$pk => $pkVal]);
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $result = false;
                $returnMsg = $e->getMessage();
            }
            // 响应处理
            if ($result !== false) {
                $this->controller->success();
            } else {
                $this->controller->error("操作失败" . (empty($returnMsg) ? $returnMsg : ": {$returnMsg}"));
            }
        }
    }
}
