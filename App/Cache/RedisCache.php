<?php

namespace App\Cache;

use EasySwoole\Component\Singleton;
use EasySwoole\RedisPool\Redis;

class RedisCache 
{
    const SPIDER_PRODUCT_QUEUE_KEY = 'product_queue';

    const SPIDER_CONSUME_QUEUE_KEY = 'consume_queue';

    public static $redisName = 'redis';

    use Singleton;

    public function push($key, $value)
    {
        return Redis::invoke(self::$redisName,function (\EasySwoole\Redis\Redis $redis) use($key,$value){
            //判断是否数组
            if(is_array($value))
            {
                $value = json_encode($value,JSON_UNESCAPED_UNICODE);
            }
            return $redis->rPush($key,$value);
        });
    }

    public function pop($key)
    {
        return Redis::invoke(self::$redisName,function (\EasySwoole\Redis\Redis $redis) use($key){
            return $redis->lPop($key);
        },0);
    }
}