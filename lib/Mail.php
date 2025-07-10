<?php

/** 
 * 配置
 * 
 * 
 * $config['mail_from'] = "帐号@域名";
 * $config['mail_smtp'] = 'smtp.mxhichina.com';
 * $config['mail_pwd'] = '密码';
 * $config['mail_port'] = '465'; //465时可不填写
 * 
 * 
 * 发送邮件
 * \lib\Mail::send([
 *     'from'   => '',
 *     'replyTo'=>'reply to this address',
 *     'to'     => 'your mail address',
 *     'subject'=> "邮件标题", 
 *     'html'   => "邮件内容",
 *     'addPart'=> [
 *         ['path'=>PATH.'/uploads/demo.pdf','name'=>'test.pdf'],
 *         ['path'=>PATH.'/uploads/demo.docx','name'=>'test1.docx'],
 *      ],
 * ]); 
 * 更多用法： https://symfony.com/doc/current/mailer.html#html-content
 */

namespace lib;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

/**
 * 发送邮件
 * composer require symfony/mailer
 * https://symfony.com/components/Mailer
 */
class Mail
{
	public static $email;
	public static $mailer;
	public static $mail_from;

	/**
	 * 添加附件
	 */
	public static function addPart($file, $file_name = '', $mime = '')
	{
		if (file_exists($file)) {
			self::$email->addPart(new DataPart(new File($file), $file_name, $mime));
		}
	}

	/**
	 *  发送邮件 
	 */
	public static function send($mail_config)
	{
		if (!self::$mailer) {
			self::init();
		}
		if (!$mail_config['from']) {
			self::$email = self::$email->from(self::$mail_from);
			unset($mail_config['from']);
		}
		foreach ($mail_config as $k => $v) {
			if ($k == 'addPart') {
				if (is_array($v)) {
					foreach ($v as $v1) {
						self::addPart($v1['path'], $v1['name'], $v1['mime']);
					}
				} else if (is_string($v)) {
					self::addPart($v);
				}
			} else {
				self::$email =  self::$email->$k($v);
			}
		}
		return self::$mailer->send(self::$email);
	}
	/**
	 * 初始化
	 */
	public static function init($dsn = '')
	{
		global $config;
		$mail_from = get_config('mail_from');
		$pwd = get_config('mail_pwd');
		$mail_smtp = get_config('mail_smtp');
		$mail_port = get_config('mail_port') ?: 465;
		$dsn = get_config('mail_dsn') ?: "smtp://" . $mail_from . ":" . $pwd . "@" . $mail_smtp . ":" . $mail_port;
		self::$mail_from = $mail_from;
		ini_set("default_socket_timeout", 3);
		$transport = Transport::fromDsn($dsn);
		self::$mailer = new Mailer($transport);
		self::$email  = new Email();
	}
}
