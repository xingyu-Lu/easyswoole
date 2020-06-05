<?php

namespace App\Cache;

use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\RedisPool\Redis;

class RedisCache 
{
    const SPIDER_PRODUCT_QUEUE_KEY = 'product_queue';

    const SPIDER_CONSUME_QUEUE_KEY = 'consume_queue';

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
}