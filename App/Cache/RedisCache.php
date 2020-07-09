<?php

namespace App\Cache;

use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\RedisPool\Redis;

class RedisCache 
{
    const SPIDER_PRODUCT_QUEUE_KEY = 'product_queue';

    const SPIDER_CONSUME_QUEUE_KEY = 'consume_queue';

    const CS_ADMIN = 'cs_admin_';

    const CS_ADMIN_FIELD_FD = 'fd';

    const CS_ADMIN_FIELD_STATUS = 'status';

    const CS_ADMIN_FD = 'cs_admin_fd_';

    const CS_ADMIN_ID = 'cs_admin_id';

    const CS_ADMIN_ONLINE_STATUS = 0; //客服在线

    const CS_ADMIN_BUSY_STATUS = 1; //客服繁忙

    const CS_ADMIN_OFFLINE_STATUS = 2; //客服下线

    const USER_FD_AND_ADMIN_ID = 'user_fd_and_admin_id_';

    const ADMIN_ID_AND_USER_FD = 'admin_id_and_user_fd_';

    public static $redisName = 'redis';

    use Singleton;

    public function push($key, $value)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key, $value) {
                //判断是否数组
                if(is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                return $redis->rPush($key, $value);
            });

            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function pop($key)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key){
                return $redis->lPop($key);
            });
            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function set($key, $value)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key, $value) {
                return $redis->set($key, $value);
            });

            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function get($key)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key){
                return $redis->get($key);
            });
            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function hSet($key, $field, $value)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key, $field, $value) {
                return $redis->hSet($key, $field, $value);
            });

            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function hMSet($key, $value)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key, $value) {
                return $redis->hMSet($key, $value);
            });

            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function hGet($key, $field)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key, $field) {
                return $redis->hGet($key, $field);
            });

            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function sAdd($key, $value)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key, $value){
                return $redis->sAdd($key, $value);
            });
            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function sMembers($key)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key){
                return $redis->sMembers($key);
            });
            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function exists($key)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key){
                return $redis->exists($key);
            });
            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function del($key)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key){
                return $redis->del($key);
            });
            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }
}