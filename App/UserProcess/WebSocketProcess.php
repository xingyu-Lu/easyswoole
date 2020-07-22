<?php

namespace App\UserProcess;

use App\Cache\RedisCache;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\ServerManager;
use Swoole\Coroutine;

class WebSocketProcess extends AbstractProcess
{

    protected function run($arg)
    {
        // TODO: Implement consume() method.
        Coroutine::create(function () {
            while (1) {
                //给所有用户发剩余用户人数
                $redis_key = RedisCache::USER_ID;
                $user_count = RedisCache::getInstance()->sCard($redis_key);
                $redis_key = RedisCache::CS_ADMIN_ID_COLLECTION;
                $admin_ids = RedisCache::getInstance()->sMembers($redis_key);
                foreach ($admin_ids as $key => $value) {
                    $redis_key = RedisCache::CS_ADMIN . $value;
                    $redis_field = RedisCache::CS_ADMIN_FIELD_FD;
                    $admin_fds = RedisCache::getInstance()->hGet($redis_key, $redis_field);
                    $admin_fds = json_decode($admin_fds, true);
                    foreach ($admin_fds as $key_1 => $value_1) {
                        $this->push($value_1, 3, '待服务用户:' . $user_count . '人');
                    }
                }
                $admin_id = RedisCache::getInstance()->pop(RedisCache::CS_ADMIN_ID);
                if (empty($admin_id)) {
                    Logger::getInstance()->console('暂无客服人员ID');
                    sleep(2);
                    continue;
                } else {
                	Logger::getInstance()->console('客服人员ID: ' . $admin_id);
                }
                $redis_key = RedisCache::CS_ADMIN_ID_COLLECTION;
                $res = RedisCache::getInstance()->sCard($redis_key);
                if ($res == 0) {
                	Logger::getInstance()->console('暂无客服人员ID');
                	continue;
                }
                $redis_key = RedisCache::CS_ADMIN . $admin_id;
                $redis_field_1 = RedisCache::CS_ADMIN_FIELD_STATUS;
                $res = RedisCache::getInstance()->hGet($redis_key, $redis_field_1);
                if (!is_null($res) && $res == RedisCache::CS_ADMIN_BUSY_STATUS) {
                	Logger::getInstance()->console('客服人员ID: ' . $admin_id . ' 繁忙');
                	continue;
                }
                $redis_key = RedisCache::USER_ID;
                $res = RedisCache::getInstance()->sCard($redis_key);
                if ($res == 0) {
                	//客服ID入队列
                	RedisCache::getInstance()->push(RedisCache::CS_ADMIN_ID, $admin_id);
                	Logger::getInstance()->console('暂无用户ID');
                    sleep(2);
                    continue;
                }
                $user_ids = RedisCache::getInstance()->sMembers($redis_key);
                //用户ID和客服ID绑定关系
            	$redis_key = RedisCache::USER_ID_BIND_CS_ADMIN_ID . $user_ids[0];
            	$redis_value = $admin_id;
            	RedisCache::getInstance()->set($redis_key, $redis_value);
            	$redis_key = RedisCache::CS_ADMIN_ID_BIND_USER_ID . $admin_id;
            	$redis_value = $user_ids[0];
            	RedisCache::getInstance()->set($redis_key, $redis_value);
            	//设置客服状态
            	$redis_key = RedisCache::CS_ADMIN . $admin_id;
            	$redis_field = RedisCache::CS_ADMIN_FIELD_STATUS;
            	$redis_value = RedisCache::CS_ADMIN_BUSY_STATUS;
            	RedisCache::getInstance()->hSet($redis_key, $redis_field, $redis_value);
                //给所有用户发送当前排队位置消息，用户初始化消息，提醒客服有用户连接进来的消息
                foreach ($user_ids as $key => $value) {
                	if ($key == 0) {
                		//用户初始化消息
                		$redis_key = RedisCache::USER_ID_BIND_USER_FD . $value;
                		$user_fd = RedisCache::getInstance()->get($redis_key);
                		$this->push($user_fd, 1, '官人您好，绿巨人为您服务！');
                		//提醒客服有用户连接进来的消息
                		$redis_key = RedisCache::CS_ADMIN . $admin_id;
                        $redis_field = RedisCache::CS_ADMIN_FIELD_FD;
                        $admin_fds = RedisCache::getInstance()->hGet($redis_key, $redis_field);
                        $admin_fds = json_decode($admin_fds, true);
                        foreach ($admin_fds as $key => $value) {
                            $this->push($value, 0, '系统提示：fd为' . $user_fd . '的用户已连接~');
                        }
                		continue;
                	}
                	$redis_key = RedisCache::USER_ID_BIND_USER_FD . $value;
                	$user_fd = RedisCache::getInstance()->get($redis_key);
                	$redis_key = RedisCache::USER_ID_LOCATION . $value;
                	$user_location = RedisCache::getInstance()->get($redis_key);
                	$this->push($user_fd, 0, '系统提示：您当前需要等候' . ($user_location-1) . '人');
                }
                Logger::getInstance()->console('客服ID： ' . $admin_id . ' 服务用户： ' . $user_ids[0]);
                //删除用户在集合的元素
                $redis_key = RedisCache::USER_ID;
                $redis_value = $user_ids[0];
                RedisCache::getInstance()->sRem($redis_key, $redis_value);
            }
        });
    }

    /*
     * 该回调可选
     * 当该进程退出的时候，会执行该回调
     */
    protected function onShutDown()
    {
        Logger::getInstance()->console('ConsumeProcess exit');
    }

    /**
     * [push 发送消息]
     * @param  int    $fd    [socket文件描述符]
     * @param  int    $type  [消息类型]
     * @param  string $msg   [消息] 
     * @return [void]        [返回值]
     */
    private function push(int $fd, int $type, string $msg) :void
    {
        $server = ServerManager::getInstance()->getSwooleServer();
        $server->push($fd, json_encode(['type' => $type, 'msg' => $msg]));
    }
}