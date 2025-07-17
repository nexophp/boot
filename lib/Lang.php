<?php

/**
 * 多语言类
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

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

    public static function trans($name, $val = [], $file_name = 'app')
    {
        $data = [
            'name' => $name,
            'value' => $val,
            'file_name' => $file_name,
        ];
        /**
         * 语言翻译
         */
        do_action('lang', $data);
        $ret = $data['return'] ?? '';
        if ($ret) {
            return $ret;
        }
        $lang = cookie('lang') ?: 'zh-cn';
        if (!self::$obj[$file_name]) {
            $route = \Route::getActions();
            $module = $route['module'];
            $files = [];
            $files[] = PATH . '/app/' . $module . '/lang/' . $lang . '/' . $file_name . '.php';
            $files[] = PATH . '/lang/' . $lang . '/' . $file_name . '.php';
            $arr = find_files($files);
            self::$obj[$file_name] = $arr;
        }
        $output =  self::$obj[$file_name][$name];
        if ($val) {
            foreach ($val as $k => $v) {
                $output = str_replace("{" . $k . "}", $v, $output);
            }
        }
        return $output ?: $name;
    }
}
