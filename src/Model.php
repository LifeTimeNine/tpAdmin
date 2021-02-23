<?php

namespace tpadmin;

use think\Model as ThinkModel;
use think\model\concern\SoftDelete;

/**
 * 模型基类
 */
abstract class Model extends ThinkModel
{
    protected $autoWriteTimestamp = true;

    protected $updateTime = false;

    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
}