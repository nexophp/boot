<?php

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
