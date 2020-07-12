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

    /**
     * [default 默认方法]
     * @return [void] [返回值]
     */
    public function default(): void
    {
        $fd = $this->caller()->getClient()->getFd();
    	$res = [
            'type' => 0,
            'msg' => '客官你似乎有点不太对劲！',
        ];
    	$this->response()->setMessage(json_encode($res));
        $this->disconnect($fd);
    }

    /**
     * [adminHeart 供客服心跳检测使用]
     * @return [void] [返回值]
     */
    public function adminHeart(): void
    {

    }

    /**
     * [endSession 客服人员结束用户会话]
     * @return [type] [返回值]
     */
    public function endSession(): void
    {
    	//解除客服ID绑定用户的fd
    	$fd = $this->caller()->getClient()->getFd();
        $args = $this->caller()->getArgs();
        if (isset($args['admin_id'])) {
        	$redis_key = RedisCache::ADMIN_ID_AND_USER_FD . $args['admin_id'];
        	$res = RedisCache::getInstance()->exists($redis_key);
        	if ($res) {
        		$user_fd = RedisCache::getInstance()->get($redis_key);
                $this->disconnect($user_fd);
                //发送消息提示客服，用户连接已断开
                $this->push($fd, ['type' => 0, 'msg'=>'系统提示：已断开用户会话~']);
        	}
        }
    }

    /**
     * [sendMessage 发送消息]
     * @return [void] [返回值]
     */
    public function sendMessage(): void
    {
    	$fd = $this->caller()->getClient()->getFd();
    	$args = $this->caller()->getArgs();
    	$res = [
            'type' => 1,
            'msg' => $args['msg'],
        ];
    	if (!empty($args['admin_id'])) {
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
    	}
        
        if (!empty($args['user_id'])) {
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
                DbManager::getInstance()->invoke(function ($client) use ($args){
                    $BlogCsMessageModel = BlogCsMessage::invoke($client);
                    $BlogCsMessageModel->msg = $args['msg'];
                    $BlogCsMessageModel->type = 0;
                    $BlogCsMessageModel->user_id = $args['user_id'];
                    $BlogCsMessageModel->create_time = time();
                    $data = $BlogCsMessageModel->save();
                    return $data;
                });
            }
    	}
    }

    /**
     * [push 发送消息]
     * @param  int    $fd    [socket文件描述符]
     * @param  array  $value [消息array]
     * @return [void]        [返回值]
     */
    private function push(int $fd, array $value) :void
    {
    	$server = ServerManager::getInstance()->getSwooleServer();
    	$server->push($fd, json_encode($value));
    }

    /**
     * [disconnect 断开连接]
     * @param  int    $fd [socket文件描述符]
     * @return [void]     [返回值]
     */
    private function disconnect(int $fd): void
    {
        $server = ServerManager::getInstance()->getSwooleServer();
        $server->disconnect($fd, 1000, '会话已结束~');
    }
}