<?php

namespace lib;
/*  
* 多语言
* $lang = 'zh-cn';
* lib\Lang::set($lang);
*/

class Lang
{
    public static $lang_dir;
    public static $obj;

    public static function set($name = 'zh-cn')
    {
        cookie('lang', $name);
        self::$lang_dir = PATH . 'lang/' . $name . '/';
    }

    public static function trans($name, $val = [], $pre = 'app')
    {
        $lang = cookie('lang') ?: 'zh-cn';
        if (!self::$obj[$pre]) {
            $file = PATH . '/lang/' . $lang . '/' . $pre . '.php';
            if (file_exists($file)) {
                $arr = include $file;
            }
            self::$obj[$pre] = $arr;
        }
        $output =  self::$obj[$pre][$name];
        if ($val) {
            foreach ($val as $k => $v) {
                $output = str_replace("{" . $k . "}", $v, $output);
            }
        }
        return $output ?: $name;
    }
}
