<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';
define('EXTEND_PATH', __DIR__ . '/../extend/');

define('APP_PATH', __DIR__ . '/../application/');
// 支持事先使用静态方法设置Request对象和Config对象
// 执行应用并响应
if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
Container::get('app')->run()->send();
