<?php

/**
 * Session类
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace lib;

class Session
{
    public static function init()
    {
        if (session_status()) {
            session_start();
        }
    }

    public static function key($name)
    {
        global  $config;
        $key   = $config['cookie_prefix'] . $name;
        return $name . $key;
    }
    /*
    * 获取
    */
    public static function get($name)
    {
        self::init();
        $name = self::key($name);
        return $_SESSION[$name];
    }
    /**
     * 设置
     */
    public static function set($name, $value)
    {
        self::init();
        $name = self::key($name);
        return $_SESSION[$name] = $value;
    }
    /**
     * 删除seesion
     */
    public static function delete($name)
    {
        self::init();
        $name = self::key($name);
        $_SESSION[$name] = '';
    }
    /**
     * session flash message
     */
    public static function _message($name, $val = '')
    {
        if ($val) {
            self::set($name, $val);
        } else {
            $val = self::get($name);
            self::delete($name);
            return $val;
        }
    }
    /**
     * set flash message
     */
    public static function set_message($name, $val)
    {
        return self::_message($name, $val);
    }
    /**
     * get flash message
     */
    public static function get_message($name)
    {
        return self::_message($name);
    }
    /**
     * check session flash message is exists
     */
    public static function has_message($name)
    {
        if (self::get($name)) {
            return true;
        } else {
            return false;
        }
    }
}
