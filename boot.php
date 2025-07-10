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
if (!defined('PATH')) define('PATH', realpath(__DIR__ . '/../../../') . '/');
/**
 * 框架路径
 */
if (!defined('NexoPHP_PATH')) define('NexoPHP_PATH', realpath(PATH . 'vendor/nexophp') . '/');
/**
 * 错误提示 
 */
if (DEBUG) {
  ini_set('display_errors', 'on');
  //error_log() 函数，记录具体错误
  error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
  ini_set('error_log', PATH . 'runtime/phplog.log');
} else {
  ini_set('display_errors', 'off');
  error_reporting(0);
}
/**
 * 数据库配置
 */
include PATH . 'config.ini.php';
/**
 * 启动数据库连接
 */
include NexoPHP_PATH . 'db/boot.php';
/**
 * 加载autoload
 */
global $autoload;
if (!$autoload) {
  $autoload = include PATH . 'vendor/autoload.php';
}
$autoload->addPsr4('app\\', PATH . 'app/');
$autoload->addPsr4('modules\\', PATH . 'modules/');
include __DIR__ . '/inc/function.php';
include __DIR__ . '/inc/cross.php';
include __DIR__ . '/inc/install.php';
include __DIR__ . '/inc/jwt.php';
include __DIR__ . '/inc/request.php';
return IRoute::do(function () {
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
