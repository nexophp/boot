<?php 
namespace lib;
use Ramsey\Uuid\Uuid;

class Str
{
	/**
	 * 生成UUID
	 * https://uuid.ramsey.dev/en/stable/quickstart.html#using-ramsey-uuid
	 */
	public static function uuid($int=4){
		if(is_numeric($int)){
			$met = "uuid".$int;	
		}else{
			$met = $int;
		}		
		return Uuid::$met()->toString();
	}
	/**
	* 使用Sonyflake生成唯一值，确保并发时生成唯一ID,最长可用174年
	* $id = \lib\Str::sony_flake_id();
	* 如果需要不同的sequence，可传值\lib\Str::sony_flake_id($center_id=0,$work_id=1);
	* config.ini.php
	* $config['redis'] = [
	* 	'host'=>'',
	* 	'port'=>'',
	* 	'auth'=>'', 
	* ];
	* 
	* $config['sony_flake'] = [ 
	* 	'from_date'=>'2022-10-27',
	* ];
	* https://github.com/godruoyi/php-snowflake
	*/
	public static function sony_flake_id($center_id=0,$work_id=0){
		global $config;
		global $snowflake_obj;
		$key = $center_id.$work_id;
		if(!$snowflake_obj[$key]){
			$redis_config = $config['redis'];
			$sony_flake  = $config['sony_flake'];
			$start_date  = $sony_flake['from_date']?:"2022-10-27"; 
			$redis = new \Redis(); 
			$redis->connect($redis_config['host'], $redis_config['port']); 
			if($redis_config['auth']){
				$redis->auth($redis_config['auth']);	
			}		
			$snowflake = new \Godruoyi\Snowflake\Sonyflake($center_id, $work_id);
			$snowflake->setStartTimeStamp(strtotime(date($start_date))*1000)
			        ->setSequenceResolver(new \Godruoyi\Snowflake\RedisSequenceResolver($redis));
			$snowflake_obj[$key] = $snowflake;
		} 
		$id = $snowflake_obj[$key]->id(); 
		return $id;
	}	
	/**
	* 生成订单号,唯一的
	* $id = \lib\Str::order_id('SP'); 
	*/
	public static function order_id($prefix = '',$center_id=0,$work_id=0)
	{
		return $prefix . date('YmdHi') .self::sony_flake_id($center_id,$work_id);
	}
	/**
	 * 500m 1km
	 * 1公里
	 * @param  mixed $dis [description]
	 * @return string     [description]
	 */
	public static function dis($dis)
	{
		$l['公里'] = 1000;
		$l['里']   = 1000;
		$l['m']    = 1;
		foreach ($l as $k => $num) {
			if (strpos($dis, $k) !== false) {
				$dis = str_replace($k, "", $dis);
				$dis = $dis * $num;
			}
		}
		return $dis;
	}
	/**
	 * 折扣 100 1 0.1折
	 * @param string $size 
	 * @return string　 
	 */
	public static function discount($price, $nowprice)
	{
		return round(10 / ($price / $nowprice), 1);
	}

	static $size = ['B', 'KB', 'MB', 'GB', 'TB'];

	/**
	 * 计算时间剩余　 
	 * 
	 * $timestamp - $small_timestamp 剩余的时间，相差几天几小时几分钟
	 * @param   $timestamp 当前时间戳
	 * @param   $small_timestamp 自定义时间戳，小于当前时间戳
	 * @return array ２天３小时２８分钟１０秒 
	 */
	public static function less_time($timestamp, $small_timestamp = null)
	{
		if (!$small_timestamp) $time = $timestamp;
		else $time = $timestamp - $small_timestamp;
		if ($time <= 0) return -1;
		$days = intval($time / 86400);
		$remain = $time % 86400;
		$hours = intval($remain / 3600);
		$remain = $remain % 3600;
		$mins = intval($remain / 60);
		$secs = $remain % 60;
		return ["d" => $days, "h" => $hours, "m" => $mins, "s" => $secs];
	}

	/**
	 * 字节单位自动转换 显示1GB MB等
	 * @param string $size 
	 * @return string　 
	 */
	public static function size($size)
	{
		$units = static::$size;
		for ($i = 0; $size >= 1024 && $i < 4; $i++) {
			$size /= 1024;
		}
		return round($size, 2) . ' ' . $units[$i];
	}
	/**
	 * 字节单位自动转换到指定的单位
	 * @param string $size 　 
	 * @param string $to 　
	 * @return string
	 */
	public static function size_to($size, $to = 'GB')
	{
		$size = strtoupper($size);
		$to = strtoupper($to);
		$arr = explode(' ', $size);
		$key = $arr[1];
		$size = $arr[0];
		$i = array_search($key, static::$size);
		$e = array_search($to, static::$size);
		$x = 1;
		if ($i < $e) {
			for ($i; $i < $e; $i++) {
				$x *= 1024;
			}
			return round($size / $x, 2);
		}
		for ($e; $e < $i; $e++) {
			$x *= 1024;
		}
		return $size * $x;
	}

	/**
	 * 随机数字
	 * @param string $j 位数 　 
	 * @return int
	 */
	public static function rand_number($j = 4)
	{
		$str = null;
		for ($i = 0; $i < $j; $i++) {
			$str .= mt_rand(0, 9);
		}
		return $str;
	}
	/**
	 * 随机字符
	 * @param string $j 位数 　 
	 * @return string
	 */
	public static function rand($j = 8)
	{
		$string = "";
		for ($i = 0; $i < $j; $i++) {
			srand((float)microtime() * 1234567);
			$x = mt_rand(0, 2);
			switch ($x) {
				case 0:
					$string .= chr(mt_rand(97, 122));
					break;
				case 1:
					$string .= chr(mt_rand(65, 90));
					break;
				case 2:
					$string .= chr(mt_rand(48, 57));
					break;
			}
		}
		return $string; //to uppercase
	}

	/**
	 * 截取后，用 ...代替被截取的部分
	 * @param  string $string 字符串
	 * @param  int $length 截取长度
	 * @return string
	 */
	public static function cut($string, $length,$append = '')
	{
		$new_str = '';
		preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $info);
		for ($i = 0; $i < count($info[0]); $i++) {
			$new_str .= $info[0][$i];
			$j = ord($info[0][$i]) > 127 ? $j + 2 : $j + 1;
			if ($j > $length - 3) {
				return $new_str . $append;
			}
		}
		return join('', $info[0]);
	}
}
