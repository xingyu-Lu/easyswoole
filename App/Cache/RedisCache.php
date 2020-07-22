<?php

namespace App\Cache;

use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\RedisPool\Redis;

class RedisCache 
{
    const SPIDER_PRODUCT_QUEUE_KEY = 'product_queue';
    const SPIDER_CONSUME_QUEUE_KEY = 'consume_queue';

    const CS_ADMIN = 'cs_admin_'; //客服ID绑定的fd及status信息(hash)
    const CS_ADMIN_FIELD_FD = 'fd'; //客服ID绑定的fd字段
    const CS_ADMIN_FIELD_STATUS = 'status'; //客服ID绑定的status字段
    const CS_ADMIN_FD_BIND_ADMIN_ID = 'cs_admin_fd_bind_admin_id_'; //客服fd绑定客服ID
    const CS_ADMIN_ID = 'cs_admin_id'; //客服ID(队列)
    const CS_ADMIN_ONLINE_STATUS = 0; //客服状态在线
    const CS_ADMIN_BUSY_STATUS = 1; //客服状态繁忙
    const CS_ADMIN_ID_COLLECTION = 'cs_admin_id_collection'; //客服ID集合

    const USER_ID_BIND_CS_ADMIN_ID = 'user_id_bind_cs_admin_id_'; //用户ID绑定客服ID
    const CS_ADMIN_ID_BIND_USER_ID = 'cs_admin_id_bind_user_id_'; //客服ID绑定用户ID

    const USER_ID_BIND_USER_FD = 'user_id_bind_user_fd_'; //用户ID绑定用户fd
    const USER_FD_BIND_USER_ID = 'user_fd_bind_user_id_'; //用户fd绑定用户ID
    const USER_ID = 'user_id'; //用户ID(集合)
    const USER_ID_LOCATION = 'user_id_location_'; //用户排队位置

    // const USER_FD_AND_ADMIN_ID = 'user_fd_and_admin_id_';

    // const ADMIN_ID_AND_USER_FD = 'admin_id_and_user_fd_';

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

    public function sRem($key, $value)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key, $value){
                return $redis->sRem($key, $value);
            });
            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function sIsMember($key, $value)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key, $value){
                return $redis->sIsMember($key, $value);
            });
            return $res;
        } catch (\Throwable $e) {
            $msg = 'msg: ' . $e->getMessage() . 'code: ' . $e->getCode() . 'file: ' . $e->getFile() . 'line: ' . $e->getLine();
            Logger::getInstance()->log($msg);
        }
    }

    public function sCard($key)
    {
        try {
            $res = Redis::invoke(self::$redisName, function (\EasySwoole\Redis\Redis $redis) use($key){
                return $redis->sCard($key);
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