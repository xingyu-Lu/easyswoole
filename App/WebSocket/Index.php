<?php
/**
 * Created by PhpStorm.
 * User: Apple
 * Date: 2018/11/1 0001
 * Time: 14:42
 */
namespace App\WebSocket;

use App\Cache\RedisCache;
use App\Models\BlogCsMessage;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\ORM\DbManager;
use EasySwoole\Socket\AbstractInterface\Controller;

/**
 * Class Index
 *
 * 此类是默认的 websocket 消息解析后访问的 控制器
 *
 * @package App\WebSocket
 */
class Index extends Controller
{
    function hello()
    {
        $this->response()->setMessage('call hello with arg:'. json_encode($this->caller()->getArgs()));
    }

    public function who(){
        $this->response()->setMessage('your fd is '. $this->caller()->getClient()->getFd());
    }

    public function default()
    {
    	$res = [
            'type' => 0,
            'msg' => '客官你似乎有点不太对劲！',
        ];
    	$this->response()->setMessage(json_encode($res));
    }

    public function endSession()
    {
    	//解除客服ID绑定用户的fd
    	$fd = $this->caller()->getClient()->getFd();
        $args = $this->caller()->getArgs();
        if (isset($args['admin_id'])) {
        	$redis_key = RedisCache::ADMIN_ID_AND_USER_FD . $args['admin_id'];
        	$res = RedisCache::getInstance()->exists($redis_key);
        	if ($res) {
        		$user_fd = RedisCache::getInstance()->get($redis_key);
                $server = ServerManager::getInstance()->getSwooleServer();
        		$server->disconnect($user_fd, 1000, '会话已结束~');
                //发送消息提示客服，用户连接已断开
                $this->push($fd, ['type' => 0, 'msg'=>'系统提示：已断开用户会话~']);
        		//客服ID入队列
        		RedisCache::getInstance()->push(RedisCache::CS_ADMIN_ID, $args['admin_id']);
        	}
        }
    }

    public function sendMessage()
    {
    	$fd = $this->caller()->getClient()->getFd();
    	$args = $this->caller()->getArgs();
    	$res = [
            'type' => 1,
            'msg' => $args['msg'],
        ];
    	if (isset($args['admin_id'])) {
    		$redis_key = RedisCache::ADMIN_ID_AND_USER_FD . $args['admin_id'];
    		$user_fd = RedisCache::getInstance()->get($redis_key);
            if ($user_fd) {
                $this->push($user_fd, $res);
                //客服消息写入表
                DbManager::getInstance()->invoke(function ($client) use ($args){
                    $BlogCsMessageModel = BlogCsMessage::invoke($client);
                    $BlogCsMessageModel->msg = $args['msg'];
                    $BlogCsMessageModel->type = 1;
                    $BlogCsMessageModel->user_id = $args['admin_id'];
                    $BlogCsMessageModel->create_time = time();
                    $data = $BlogCsMessageModel->save();
                    return $data;
                });
            }
    		$redis_key = RedisCache::CS_ADMIN . $args['admin_id'];
    		$redis_field = RedisCache::CS_ADMIN_FIELD_FD;
    		$admin_fds = RedisCache::getInstance()->hGet($redis_key, $redis_field);
    		$admin_fds = json_decode($admin_fds, true);
    		foreach ($admin_fds as $key => $value) {
    			if ($value == $fd) {
    				continue;
    			}
                $res['type'] = 2;
    			$this->push($value, $res);
    		}
    	} else {
    		$redis_key = RedisCache::USER_FD_AND_ADMIN_ID . $fd;
    		$admin_id = RedisCache::getInstance()->get($redis_key);
    		$redis_key = RedisCache::CS_ADMIN . $admin_id;
    		$redis_field = RedisCache::CS_ADMIN_FIELD_FD;
    		$admin_fds = RedisCache::getInstance()->hGet($redis_key, $redis_field);
            if ($admin_fds) {
                $admin_fds = json_decode($admin_fds, true);
                foreach ($admin_fds as $key => $value) {
                    $this->push($value, $res);
                }
                //用户消息写入表
                DbManager::getInstance()->invoke(function ($client) use ($args, $fd){
                    $BlogCsMessageModel = BlogCsMessage::invoke($client);
                    $BlogCsMessageModel->msg = $args['msg'];
                    $BlogCsMessageModel->type = 0;
                    $BlogCsMessageModel->user_id = $fd;
                    $BlogCsMessageModel->create_time = time();
                    $data = $BlogCsMessageModel->save();
                    return $data;
                });
            }
    	}
    }

    private function push($fd, $value) :void
    {
    	$server = ServerManager::getInstance()->getSwooleServer();
    	$server->push($fd, json_encode($value));
    }
}