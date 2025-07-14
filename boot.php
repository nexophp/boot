<?php

/**
 * 版本号
 */
define('VERSION', "3.0.0");
/**
 * 线上可以关闭PHP错误提示
 */
if (!defined('DEBUG')) define('DEBUG', true);
/**
 * 项目路径
 */
if (!defined('PATH')) define('PATH', realpath(__DIR__ . '/../../..'));
if (!defined('WWW_PATH')) define('WWW_PATH', PATH . '/public');
/**
 * 框架路径
 */
if (!defined('NEXOPHP_PATH')) define('NEXOPHP_PATH', realpath(PATH . '/vendor/nexophp'));
/**
 * 错误提示 
 */
if (DEBUG) {
  ini_set('display_errors', 'on');
  //error_log() 函数，记录具体错误
  error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
  ini_set('error_log', PATH . '/runtime/phplog.log');
} else {
  ini_set('display_errors', 'off');
  error_reporting(0);
}
/**
 * 数据库配置
 */
require PATH . '/config.ini.php';
/**
 * 启动数据库连接
 */
require NEXOPHP_PATH . '/db/boot.php';
/**
 * 加载autoload
 */
global $autoload;
$autoload = require PATH . '/vendor/autoload.php';
$autoload->addPsr4('app\\', PATH . '/app/');
$autoload->addPsr4('modules\\', PATH . '/modules/');
require __DIR__ . '/inc/addon.php';
require __DIR__ . '/inc/function.php';
require __DIR__ . '/inc/jwt.php';
require __DIR__ . '/inc/request.php';
/**
 * TRIM
 */
global_trim();
/**
 * 允许跨域
 */
allow_cross_origin();
/**
 * 页面渲染头部
 */
add_action("header", function () {
  render_css_file();
  render_css();
  render_js_file();
});

/**
 * 页面渲染底部
 */
add_action("footer", function () {
  render_js();
});
/**
 * 应用初始化
 */
do_action("app.init");
/**
 * 路由首页
 */
Route::all('/', function () {
  /**
   * 首页
   */
  do_action("index");
});
/**
 * 执行路由
 */
return Route::do(function () {
  /**
   * 路由后
   */
  do_action("route.end");
}, function () {
  /**
   * 路由不存在
   */
  do_action("route.not_find");
});
