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


/**
 * 
 * $lang = 'zh-cn';
 * lib\Validate::lang($lang);
 * lib\Validate::langDir(__DIR__.'/validator_lang');
 */
class Validate extends \Valitron\Validator
{

    public function errors($field = null)
    {
        if ($field !== null) {
            return isset($this->_errors[$field]) ? $this->_errors[$field] : false;
        }
        return $this->_errors;
    }
}
