<?php

/**
 * 验证类
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */


namespace lib;

/**
 * https://github.com/vlucas/valitron 
 */
 
class Validate extends \Valitron\Validator
{

    public function __construct($data = array(), $fields = array(), $lang = null, $langDir = null)
    {
        global $config; 
        $this->_fields = !empty($fields) ? array_intersect_key($data, array_flip($fields)) : $data; 
        $lang = $config['_lang']; 
        $langDir = $langDir ?: static::langDir(); 
        $langFile = rtrim($langDir, '/') . '/' . $lang . '.php';
        if (stream_resolve_include_path($langFile)) {
            $langMessages = include $langFile;
            static::$_ruleMessages = array_merge(static::$_ruleMessages, $langMessages);
        } else {
            throw new \InvalidArgumentException("Fail to load language file '" . $langFile . "'");
        }
    }

    public function errors($field = null)
    {
        if ($field !== null) {
            return isset($this->_errors[$field]) ? $this->_errors[$field] : false;
        }
        return $this->_errors;
    }
}
