<?php

/**
 * JWT 
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

use Firebase\JWT\JWT as Firebase_JWT;

/**
 * 是否是管理员
 */
function is_admin($uid = '')
{
	$user = get_user($uid);
	if ($user && $user['tag'] == 'admin') {
		return true;
	}
}
/**
 * 获取用户信息
 */
function get_user($uid = '')
{
	$uid = $uid ?: cookie('uid');
	return db_get_one('user', "*", ['id' => $uid]);
}
/**
 * 返回接口AUTHORIZATION解密后数组
 * 返回{user_id:'',time:int}
 */
function api($show_error = true)
{
	static $api_data;
	if (!$api_data) {
		if (cookie('uid')) {
			$user = get_user(cookie('uid'));
			$user['user_id'] = $user['id'];
			$api_data = $user;
		}
		if (!$api_data) {
			$api_data = get_author($show_error);
		}
	}
	return $api_data;
}
/**
 * 接口是否是管理员
 */
function api_admin()
{
	$arr = api();
	if ($arr['tag'] != 'admin') {
		json_error(['msg' => lang('Access Deny')]);
	}
}

/**
 * 解析 HTTP_AUTHORIZATION 
 */
function get_author($show_error = true)
{
	global $config;
	$sign  = $_SERVER['HTTP_AUTHORIZATION'] ?: g('sign');
	if (!$sign) {
		$error = '参数错误';
	}
	$jwt  = Jwt::decode($sign);
	if (!$jwt->time) {
		$error = '缺少time参数';
	}
	$exp = $config['jwt_exp_time'] ?: 120;
	if ($jwt->time + $exp < time()) {
		$error = 'Token过期';
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
		if (!$key) {
			$key   = $config['jwt_key'];
		}
		$time     = time();
		$exp_time = time() + 10;
		$payload  = array(
			"iat" => $time,
			"nbf" => $time,
			"exp" => $exp_time,
		) + $data;
		$jwt = Firebase_JWT::encode($payload, $key);
		return base64_encode($jwt);
	}

	public static function decode($value, $key = null)
	{
		global $config;
		$value   = base64_decode($value);
		if (!$key) {
			$key     = $config['jwt_key'];
		}
		try {
			$arr     = Firebase_JWT::decode($value, $key, array('HS256'));
			return $arr;
		} catch (\Exception $e) {
		}
	}
}
