# tpadmin
一个基于ThinkPHP5.1的后台管理库。  

## 环境要求
- Apache/Nginx
- PHP7.1+
- MySQL5.6+
- Composer

## 安装

1. 使用 Composer 安装ThinkPHP5.1  
在命令行下面，切换到你的WEB根目录下面并执行下面的命令：
```
composer create-project topthink/think=5.1.* tpadmin
```
2. 使用 Composer 安装 tpadmin 扩展包：
```
composer require-project lifetime/tpadmin
```
3. 配置数据库  
ThinkPHP5.1 的数据库配置文件在 /config/database.php ，配置数据库相关参数：
```php
<?php
return [
    // 数据库类型
    'type'            => 'mysql',
    // 服务器地址
    'hostname'        => '127.0.0.1',
    // 数据库名
    'database'        => 'tpadmin',
    // 用户名
    'username'        => 'root',
    // 密码
    'password'        => 'root',
    // 端口
    'hostport'        => '3306',
];
```
4. 将扩展包导入框架  
将命令行切换至 ThinkPHP 根目录，然后执行命令：
~~~
php think tpadmin:install
~~~
此命令会自动创建 admin 模块，并且将相关数据表导入数据库。
> 注意：在执行此命令之前请确保没有 admin 模块，并且数据库没有任何数据表！！！

## 控制器
> 后台管理页面的控制器建议继承 tpadmin\Controller 类。

后台权限基于注解实现
auth 是否验证此节点
menu 是否是菜单
示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

/**
 * 首页
 * @calss Index
 */
class Index extends Controller
{
  /**
   * 首页
   * @auth  true
   * @menu  true
  */
  public function index()
  {
    if (1 == 0) {
      $this->success('1等于0'); // 返回成功消息
    } else {
      $this->error('1不等于0'); // 返回失败消息
    }
  }
}
```

> $this->title 可以设置当前页面的标题

### success/error 返回 成功/失败 的消息
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  public function index()
  {
    if (1 == 0) {
      $this->success('1等于0'); // 返回成功消息
    } else {
      $this->error('1不等于0'); // 返回失败消息
    }
  }
}
```
返回的消息为：
```json
{
  "code": 1, // 消息代码 1成功，0失败
  "info": "", // 消息内容
  "data": {} // 消息数据
}
```

### fetch 返回视图
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  public function index()
  {
    $this->fetch(); 
  }
}
```
第一个参数为模板名称，如果为空表示当前操作的模板文件,第二个参数为模板变量;
> fetch 会将控制器中的属性作为也做模板变量,可以在模板文件中使用。

### redirect 重定向
同 thinkphp 的 redirect 方法

### _model 快捷模型初始化
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User'; // 控制器默认操作模型
  public function index()
  {
    $user = $this->_model('\\app\\model\\User'); // 如果不传入模型名称，默认会使用默认操作模型
  }
}
```
返回的是 tpadmin\Model 实例，具体操作方法请参考 模型 ；

### _save 快捷更新
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User'; // 控制器默认操作模型
  public function index()
  {
    $this->_save('\\app\\model\\User',['status'=>1]); // 如果不传入模型名称，默认会使用默认操作模型
  }
}
```

### _delete 快捷删除
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User'; // 控制器默认操作模型
  public function index()
  {
    $this->_delete('\\app\\model\\User',true); // 如果不传入模型名称，默认会使用默认操作模型
    // 第二个参数为 是否软删除
  }
}
```
### _form 快捷表单
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User'; // 控制器默认操作模型
  protected $validate = '\\app\\validate\\User'; // 控制器默认验证器
  public function index()
  {
    $this->_form('\\app\\model\\User','.add'); // 如果不传入模型名称或为空，默认会使用默认操作模型
    // 第二个参数为 验证器 可以传入[名称.场景],也可以为[[.场景(需要在控制器中设置属性validate)]]
  }
}
```
### 额外方法

1. 表单过滤器  
  在控制器中定义一个非私有（private）的方法 _form_filter ：

```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User'; // 控制器默认操作模型
  protected $validate = '\\app\\validate\\User'; // 控制器默认验证器
  public function add()
  {
    $this->_form('\\app\\model\\User','.add'); // 如果不传入模型名称或为空，默认会使用默认操作模型
    // 第二个参数为 验证器 可以传入[名称.场景],也可以为[[.场景(需要在控制器中设置属性validate)]]
  }
  protected _form_filter(&$data)
  {
    if ($this->request->isGet()) {
      // 加载页面之前对数据的处理
    } else {
      // 表单提交之后，写入数据库之前对数据处理
    }
  }
  // 也可以指定某个操作方法，会优先执行此方法，并且不会再执行其他的方法
  protected _add_form_filter(&$data)
  {
    if ($this->request->isGet()) {
      // 加载页面之前对数据的处理
    } else {
      // 表单提交之后，写入数据库之前对数据处理
    }
  }
}
```
2. 更新额外操作  
在控制器中定义一个非私有（private）的方法 _save_extra ：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;
use tpadmin\model\SystemLog;

class Index extends Controller
{
  protected $model = '\\app\\model\\User'; // 控制器默认操作模型
  public function index()
  {
    $this->_save('\\app\\model\\User',['status'=>1]); // 如果不传入模型名称，默认会使用默认操作模型
  }
  public function _save_extra($data)
  {
    SystemLog::write('系统管理', ($data['status'] == 1 ? '启用' : '禁用').'系统用户');
  }
  // 也可以指定某个操作方法，会优先执行此方法，并且不会再执行其他的方法
  public function _index_save_extra($data)
  {

  }
}
```
3. 删除额外操作  
在控制器中定义一个非私有（private）的方法 _delete_extra ：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;
use tpadmin\model\SystemLog;

class Index extends Controller
{
  protected $model = '\\app\\model\\User'; // 控制器默认操作模型
  public function index()
  {
    $this->_delete('\\app\\model\\User'); // 如果不传入模型名称，默认会使用默认操作模型
  }
  public function _delete_extra()
  {
    SystemLog::write('系统管理', '删除系统用户');
  }
  // 也可以指定某个操作方法，会优先执行此方法，并且不会再执行其他的方法
  public function _index_delete_extra()
  {

  }
}
```

## 模型
> 建议继承 tpadmin\Model 类。
Model 类默认设置软删除字段为 delete_time , 如果不需要软删除，请设置属性: 
```php
<?php
namespace app\mode;

use tpadmin\Model;

class User extends Model
{
  protected $deleteTime = false;
}
>
```

模型在控制器中使用 _model 方法初始化之后可以使用快捷方法，同时也可以使用 ThinkPHP 模型支持的方法。支持链式调用。

> 一下方法只介绍使用方法，具体请参考方法注释

### _page 快捷分页
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User';

  public function index()
  {
    $user = $this->_model();
    $user->page();
  }
}
>
```

### _like/_notLike 快捷Like/NotLike 查询
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User';

  public function index()
  {
    $user = $this->_model();
    $user->_like('name,nickname')->_notLike(['mobile','email']);
    $user->page();
  }
}
>
```
### _eq/_notEq 快捷Eq/NotEq 查询
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User';

  public function index()
  {
    $user = $this->_model();
    $user->_eq('sex,age')->_notEq(['status','lavel']);
    $user->page();
  }
}
>
```
### _in/_notIn 快捷In/NotIn查询
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User';

  public function index()
  {
    $user = $this->_model();
    $user->_in('sex,age')->_notIn(['status','lavel']);
    $user->page();
  }
}
>
```
### _in/_notIn 快捷In/NotIn查询
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User';

  public function index()
  {
    $user = $this->_model();
    $user->_in('sex,age')->_notIn(['status','lavel']);
    $user->page();
  }
}
>
```
### _between/_notBetween 快捷Between/NotBetween查询
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User';

  public function index()
  {
    $user = $this->_model();
    $user->_between('age')->_notBetween(['lavel']);
    $user->page();
  }
}
>
```
### _dateBetween/_timestampBetween 快捷日期区间查询/快捷时间戳区间查询
使用示例：
```php
<?php
namespace app\index\controller;

use tpadmin\Controller;

class Index extends Controller
{
  protected $model = '\\app\\model\\User';

  public function index()
  {
    $user = $this->_model();
    $user->_dateBetween('create_time')->_timestampBetween(['last_login_time']);
    $user->page();
  }
}
>
```
### _select 快捷Select
返回 Model select方法返回的数据