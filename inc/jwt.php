<?php

/**
 * JWT 
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

use Firebase\JWT\JWT as Firebase_JWT;

/**
 * 解析 HTTP_AUTHORIZATION 
 */
function get_authorization($show_error = true)
{
	$sign  = $_SERVER['HTTP_AUTHORIZATION'] ?: g('sign');
	if (!$sign) {
		$error = '参数错误';
	}
	$jwt  = Jwt::decode($sign);
	if (!$jwt->time) {
		$error = '缺少time参数';
	}
	if ($jwt->user_id) {
	} else {
		$error = '错误user_id参数';
	}
	if ($error) {
		if ($show_error) {
			json_error(['msg' => lang($error)]);
		}
	}
	return (array)$jwt;
}

/**
 * $s = Jwt::encode(['user_id'=>100,'t'=>['s'=>2]]);
 * pr(Jwt::decode($s));
 */
class Jwt
{
	public static function encode($data, $key = null)
	{
		global $config;
		$key = $key ?: $config['jwt_key'];
		$time = time();
		$jti = bin2hex(random_bytes(16));
		$payload  = array(
			"iat" => $time,
			"nbf" => $time,
			"exp" => time() + 86400,
			"jti" => $jti,
			'device' => self::getDeviceFingerprint(),
		) + $data;
		$jwt = Firebase_JWT::encode($payload, $key);
		return base64_encode($jwt);
	}

	public static function decode($value, $key = null)
	{
		global $config;
		$value   = base64_decode($value);
		$key = $key ?: $config['jwt_key'];
		try {
			$arr = Firebase_JWT::decode($value, $key, array('HS256'));
			return $arr;
		} catch (\Exception $e) {
		}
	}

	private static function getDeviceFingerprint()
	{
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
		$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
		return md5($ip . $ua);
	}
}
