<?php

/**
 * 数据脱敏
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace lib;

class DataMasker
{
    /**
     * 脱敏手机号码
     * @param string $phone 手机号码
     * @return string 脱敏后的手机号码
     */
    public static function phone($phone)
    {
        if (empty($phone)) {
            return '';
        }
        // 保留前3位和后4位，中间用****代替
        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }

    /**
     * 脱敏身份证号码
     * @param string $idCard 身份证号码
     * @return string 脱敏后的身份证号码
     */
    public static function idCard($idCard)
    {
        if (empty($idCard)) {
            return '';
        }
        // 保留前6位和后4位，中间用********代替
        return substr($idCard, 0, 6) . '********' . substr($idCard, -4);
    }

    /**
     * 脱敏银行卡号
     * @param string $bankCard 银行卡号
     * @return string 脱敏后的银行卡号
     */
    public static function bankCard($bankCard)
    {
        if (empty($bankCard)) {
            return '';
        }
        // 保留前4位和后4位，中间用****代替
        return substr($bankCard, 0, 4) . '****' . substr($bankCard, -4);
    }

    /**
     * 脱敏姓名
     * @param string $name 姓名
     * @return string 脱敏后的姓名
     */
    public static function name($name)
    {
        if (empty($name)) {
            return '';
        }
        // 中文姓名保留姓，名字用*代替
        $length = mb_strlen($name, 'UTF-8');
        if ($length <= 2) {
            return mb_substr($name, 0, 1, 'UTF-8') . '*';
        }
        return mb_substr($name, 0, 1, 'UTF-8') . str_repeat('*', $length - 2) . mb_substr($name, -1, 1, 'UTF-8');
    }

    /**
     * 脱敏邮箱
     * @param string $email 邮箱地址
     * @return string 脱敏后的邮箱地址
     */
    public static function email($email)
    {
        if (empty($email)) {
            return '';
        }
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }
        $username = $parts[0];
        $domain = $parts[1];
        $len = strlen($username);
        if ($len <= 3) {
            return substr($username, 0, 1) . '**@' . $domain;
        }
        return substr($username, 0, 2) . str_repeat('*', $len - 3) . substr($username, -1) . '@' . $domain;
    }

    /**
     * 自动识别并脱敏字符串中的敏感数据
     * @param string $input 待处理的字符串
     * @return string 脱敏后的字符串
     */
    public static function auto($input)
    {
        if (empty($input)) {
            return '';
        }

        $output = $input;

        // 手机号码（11位数字）
        $phonePattern = '/\b1[3-9]\d{9}\b/';
        $output = preg_replace_callback($phonePattern, function ($matches) {
            return self::phone($matches[0]);
        }, $output);

        // 身份证号码（15或18位）
        $idCardPattern = '/\b\d{15}(?:\d{2}[0-9Xx])?\b/';
        $output = preg_replace_callback($idCardPattern, function ($matches) {
            return self::idCard($matches[0]);
        }, $output);

        // 银行卡号（16-19位数字）
        $bankCardPattern = '/\b\d{16,19}\b/';
        $output = preg_replace_callback($bankCardPattern, function ($matches) {
            return self::bankCard($matches[0]);
        }, $output);

        // 邮箱地址
        $emailPattern = '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/';
        $output = preg_replace_callback($emailPattern, function ($matches) {
            return self::email($matches[0]);
        }, $output);

        // 中文姓名（2-4个中文字符）
        $namePattern = '/[\x{4e00}-\x{9fa5}]{2,4}\b/u';
        $output = preg_replace_callback($namePattern, function ($matches) {
            return self::name($matches[0]);
        }, $output);

        return $output;
    }
}
