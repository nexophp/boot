<?php

/**
 * 数据脱敏处理类
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace core;

use InvalidArgumentException;


class DataMasker
{
    private const PHONE_MIN_LENGTH = 7;
    private const IDCARD_MIN_LENGTH = 8;
    private const VERIFICATION_CODE_MIN_LENGTH = 2;
    private const MASK_CHAR = '*';
    private const PHONE_MASK_LENGTH = 4;
    private const EMAIL_MASK_LENGTH = 3;

    /**
     * 手机号脱敏
     *
     * @param string|null $phone 待脱敏的手机号
     * @return string|null 脱敏后的手机号
     * @throws InvalidArgumentException 如果输入无效
     */
    public static function phone(?string $phone): ?string
    {
        if (empty($phone)) {
            return $phone;
        }

        $phone = trim($phone);
        if (strlen($phone) < self::PHONE_MIN_LENGTH) {
            throw new InvalidArgumentException('手机号长度不足');
        }

        return substr($phone, 0, 3) . str_repeat(self::MASK_CHAR, self::PHONE_MASK_LENGTH) . substr($phone, -4);
    }

    /**
     * 邮箱地址脱敏
     *
     * @param string|null $email 待脱敏的邮箱地址
     * @return string|null 脱敏后的邮箱地址
     * @throws InvalidArgumentException 如果输入无效
     */
    public static function email(?string $email): ?string
    {
        if (empty($email) || !str_contains($email, '@')) {
            return $email;
        }

        $email = trim($email);
        [$prefix, $domain] = explode('@', $email, 2);

        $prefixLength = strlen($prefix);
        if ($prefixLength <= 1) {
            $maskedPrefix = $prefix[0] . str_repeat(self::MASK_CHAR, self::EMAIL_MASK_LENGTH);
        } else {
            $maskedPrefix = $prefix[0] . str_repeat(self::MASK_CHAR, min(self::EMAIL_MASK_LENGTH, $prefixLength - 1)) . substr($prefix, -1);
        }

        return $maskedPrefix . '@' . $domain;
    }

    /**
     * 身份证号脱敏
     *
     * @param string|null $idCard 待脱敏的身份证号
     * @return string|null 脱敏后的身份证号
     * @throws InvalidArgumentException 如果输入无效
     */
    public static function idCard(?string $idCard): ?string
    {
        if (empty($idCard)) {
            return $idCard;
        }

        $idCard = trim($idCard);
        if (strlen($idCard) < self::IDCARD_MIN_LENGTH) {
            throw new InvalidArgumentException('身份证号长度不足');
        }

        return substr($idCard, 0, 4) . str_repeat(self::MASK_CHAR, strlen($idCard) - self::IDCARD_MIN_LENGTH) . substr($idCard, -4);
    }

    /**
     * 验证码脱敏
     *
     * @param string|null $code 待脱敏的验证码
     * @return string|null 脱敏后的验证码
     * @throws InvalidArgumentException 如果输入无效
     */
    public static function verificationCode(?string $code): ?string
    {
        if (empty($code)) {
            return $code;
        }

        $code = trim($code);
        $length = strlen($code);
        if ($length <= self::VERIFICATION_CODE_MIN_LENGTH) {
            return str_repeat(self::MASK_CHAR, $length);
        }

        return $code[0] . str_repeat(self::MASK_CHAR, $length - 2) . $code[$length - 1];
    }

    /**
     * 自动识别并脱敏敏感数据
     *
     * @param string|null $data 待脱敏的数据
     * @return string|null 脱敏后的数据
     * @throws InvalidArgumentException 如果输入无效
     */
    public static function auto(?string $data): ?string
    {
        if (empty($data)) {
            return $data;
        }

        $data = trim($data);

        // 手机号检测 (11位数字，以1[3-9]开头)
        if (preg_match('/^1[3-9]\d{9}$/', $data)) {
            return self::phone($data);
        }

        // 邮箱检测
        if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
            return self::email($data);
        }

        // 身份证检测 (15或18位，最后可能为X)
        if (
            preg_match('/^[1-9]\d{5}(19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/', $data) ||
            preg_match('/^[1-9]\d{7}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}$/', $data)
        ) {
            return self::idCard($data);
        }

        // 验证码检测 (4-6位数字或字母)
        if (preg_match('/^[A-Za-z0-9]{4,6}$/', $data)) {
            return self::verificationCode($data);
        }

        // 默认返回原始数据
        return $data;
    }
}
