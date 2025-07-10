<?php

namespace lib;

/**
 * https://github.com/vlucas/valitron 
 */

use Valitron\Validator;

/**
 * 
 * $lang = 'zh-cn';
 * lib\Validate::lang($lang);
 * lib\Validate::langDir(__DIR__.'/validator_lang');
 */
class Validate extends Validator
{

    public function errors($field = null)
    {
        if ($field !== null) {
            return isset($this->_errors[$field]) ? $this->_errors[$field] : false;
        }
        return $this->_errors;
    }
}

/**
 * 添加简单手机号验证 
 */
\Valitron\Validator::addRule('phonech', function ($field, $value, array $params, array $fields) {
    if (preg_match('/^1\d{10}$/', $value)) {
        return true;
    } else {
        return false;
    }
}, '格式错误');
