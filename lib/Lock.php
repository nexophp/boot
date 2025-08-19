<?php

/**
 * 锁类 (基于 Predis)
 * @author sunkangchina <68103403@qq.com>
 * @license MIT <https://mit-license.org/>
 * @date 2025
 */

namespace lib;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\RedisStore;
use Predis\Client;

/**
 * https://symfony.com/doc/current/components/lock.html
 * global $config; 
 * $config['redis'] = [
 *     'scheme' => 'tcp',
 *     'host'   => '127.0.0.1',
 *     'port'   => 6379,
 *     'password' => null,
 *     'database' => 0,
 *     'timeout' => 5.0,
 * ];
 */
class Lock
{
    public static $lock;
    public static $factory;
    public static $key;

    public static function init()
    {
        global $config;
        $redis_config = $config['redis'];

        if (self::$factory) {
            return self::$factory;
        }

        // 使用 Predis 客户端
        $predis = new Client([
            'scheme'   => $redis_config['scheme'] ?? 'tcp',
            'host'     => $redis_config['host'],
            'port'     => $redis_config['port'],
            'password' => $redis_config['auth'] ?? null,
            'database' => $redis_config['database'] ?? 0,
            'timeout'  => $redis_config['timeout'] ?? 5.0,
        ]);

        $store = new RedisStore($predis);
        self::$factory = new LockFactory($store);

        return self::$factory;
    }

    public static function start($key, $time = 10)
    {
        $factory = self::init();
        self::$key = $key;
        $lock = $factory->createLock($key, $time, false);
        self::$lock = $lock;

        if (!$lock->acquire()) {
            return false;
        }

        return true;
    }

    public static function end()
    {
        if (self::$lock) {
            self::$lock->release();
        }
    }

    public static function do($key, callable $call, $time = 10)
    {
        $factory = self::init();
        self::$key = $key;
        $lock = $factory->createLock($key, $time, false);

        if (!$lock->acquire()) {
            return false;
        }

        try {
            return $call();
        } finally {
            $lock->release();
        }
    }
}
