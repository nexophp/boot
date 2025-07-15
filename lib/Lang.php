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
            $route = \Route::getActions();
            $module = $route['module'];
            $files = [];
            $files[] = PATH . '/app/' . $module . '/lang/' . $lang . '/' . $pre . '.php';
            $files[] = PATH . '/lang/' . $lang . '/' . $pre . '.php';
            $arr = find_files($files);
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
