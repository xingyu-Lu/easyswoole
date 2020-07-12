<?php

namespace App\WebSocket;

use App\Cache\RedisCache;
use EasySwoole\EasySwoole\ServerManager;

class WebSocketEvent
{
    /**
     * 握手事件
     * @param  swoole_http_request $request swoole http request
     * @param  swoole_http_response $response swoole http response
     * @return bool                         是否通过握手
     */
    public function onHandShake(\swoole_http_request $request, \swoole_http_response $response)
    {
        // 通过自定义握手 和 RFC ws 握手验证
        if ($this->customHandShake($request, $response) && $this->secWebsocketAccept($request, $response)) {
            // 接受握手 还需要101状态码以切换状态
            $response->status(101);
            var_dump('shake success at fd :' . $request->fd);
            $response->end();
            return true;
        }

        $response->end();
        return false;
    }

    /**
     * 打开了一个链接
     * @param swoole_websocket_server $server
     * @param swoole_http_request $request
     */
    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request): void
    {
        $token = isset($request->get['token']) ? $request->get['token'] : '';
        $token = explode('@', $token);
        $token_arr['admin'] = isset($token[0]) ? $token[0] : '';
        $token_arr['user_id'] = isset($token[1]) ? $token[1] : '';
        if (isset($token_arr['admin']) && $token_arr['admin'] == md5('admin')) {
            //客服ID入队列
            RedisCache::getInstance()->push(RedisCache::CS_ADMIN_ID, $token_arr['user_id']);
            //查询设置客服ID绑定fd及状态
            $redis_key = RedisCache::CS_ADMIN . $token_arr['user_id'];
            $redis_field_1 = RedisCache::CS_ADMIN_FIELD_FD;
            $redis_field_2 = RedisCache::CS_ADMIN_FIELD_STATUS;
            $admin_fds = RedisCache::getInstance()->hGet($redis_key, $redis_field_1);
            if ($admin_fds) {
                $admin_fds = json_decode($admin_fds, true);
                $admin_fds[] = $request->fd;
                $admin_fds = json_encode($admin_fds);
            } else {
                $admin_fds = json_encode([$request->fd]);
            }
            
            $redis_value = [
                $redis_field_1 => $admin_fds,
                $redis_field_2 => RedisCache::CS_ADMIN_ONLINE_STATUS,
            ];
            RedisCache::getInstance()->hMSet($redis_key, $redis_value);
            //客服fd绑定客服ID
            $redis_key = RedisCache::CS_ADMIN_FD . $request->fd;
            RedisCache::getInstance()->set($redis_key, $token_arr['user_id']);
            //提示客服已连接
            $res = [
                'type' => 0,
                'msg' => '系统提示：您已连接上~',
            ];
            $server->push($request->fd, json_encode($res));
        } else {
            while (1) {
                $admin_id = RedisCache::getInstance()->pop(RedisCache::CS_ADMIN_ID);
                if (empty($admin_id)) {
                    $res = [
                        'type' => 0,
                        'msg' => '系统提示：客官目前暂无客服人员或者客服繁忙，请稍后再试~',
                    ];
                    $server->push($request->fd, json_encode($res));
                    break;
                } else {
                    $redis_key = RedisCache::CS_ADMIN . $admin_id;
                    $redis_field = RedisCache::CS_ADMIN_FIELD_STATUS;
                    $cs_admin_status = RedisCache::getInstance()->hGet($redis_key, $redis_field);
                    if (!is_null($cs_admin_status) && $cs_admin_status == RedisCache::CS_ADMIN_ONLINE_STATUS) {
                        //用户fd绑定客服ID
                        RedisCache::getInstance()->set(RedisCache::USER_FD_AND_ADMIN_ID . $request->fd, $admin_id);
                        //客服ID绑定用户fd
                        RedisCache::getInstance()->set(RedisCache::ADMIN_ID_AND_USER_FD . $admin_id, $request->fd);
                        //设置客服状态为繁忙
                        $redis_key = RedisCache::CS_ADMIN . $admin_id;
                        $redis_field = RedisCache::CS_ADMIN_FIELD_STATUS;
                        $redis_value = RedisCache::CS_ADMIN_BUSY_STATUS;
                        $cs_admin_status = RedisCache::getInstance()->hGet($redis_key, $redis_field, $redis_value);
                        $res = [
                            'type' => 1,
                            'msg' => '官人您好，绿巨人为您服务！',
                        ];
                        //给用户发送默认消息
                        $server->push($request->fd, json_encode($res));
                        //给客服发送新用户连接的消息
                        $redis_field = RedisCache::CS_ADMIN_FIELD_FD;
                        $admin_fds = RedisCache::getInstance()->hGet($redis_key, $redis_field);
                        $admin_fds = json_decode($admin_fds, true);
                        foreach ($admin_fds as $key => $value) {
                            $server->push($value, json_encode(['type' => 0, 'msg' => '系统提示：fd为' . $request->fd . '的用户已连接']));
                        }
                        break;
                    }
                }
            }
        }
    }

    /**
     * 关闭事件
     * @param  swoole_server $server    swoole server
     * @param  int           $fd        fd
     * @param  int           $reactorId 线程id
     * @return void
     */
    public function onClose(\swoole_server $server, int $fd, int $reactorId): void
    {
        // 判断连接是否为 WebSocket 客户端 详情 参见 https://wiki.swoole.com/wiki/page/490.html
        $connection = $server->connection_info($fd);

        // 判断连接是否为 server 主动关闭 参见 https://wiki.swoole.com/wiki/page/p-event/onClose.html
        $reactorId < 0 ? '主动' : '被动';

        //解除客服ID与用户fd的绑定关系(用户断线时)
        $redis_key = RedisCache::USER_FD_AND_ADMIN_ID . $fd;
        $res = RedisCache::getInstance()->exists($redis_key);
        if ($res) {
            $admin_id = RedisCache::getInstance()->get($redis_key);
            //删掉用户fd与客服ID的绑定
            RedisCache::getInstance()->del($redis_key);
            $redis_key = RedisCache::ADMIN_ID_AND_USER_FD . $admin_id;
            //删掉客服ID与用户fd的绑定
            RedisCache::getInstance()->del($redis_key);
            //客服ID入队列
            RedisCache::getInstance()->push(RedisCache::CS_ADMIN_ID, $admin_id);
            //发送消息提示用户客服已断开连接
            $redis_key = RedisCache::CS_ADMIN . $admin_id;
            $redis_field = RedisCache::CS_ADMIN_FIELD_FD;
            $admin_fds = RedisCache::getInstance()->hGet($redis_key, $redis_field);
            $admin_fds = json_decode($admin_fds, true);
            $ServerManager = ServerManager::getInstance()->getSwooleServer();
            foreach ($admin_fds as $key => $value) {
                $ServerManager->push($value, json_encode(['type' => 0, 'msg' => '系统提示：用户已断连接~']));
            } 
        }

        //解除客服ID与用户fd的绑定关系(客服断线时)
        $redis_key = RedisCache::CS_ADMIN_FD . $fd;
        $res = RedisCache::getInstance()->exists($redis_key);
        if ($res) {
            $admin_id = RedisCache::getInstance()->get($redis_key);
            $redis_key = RedisCache::ADMIN_ID_AND_USER_FD . $admin_id;
            $res = RedisCache::getInstance()->exists($redis_key);
            if ($res) {
                $user_fd = RedisCache::getInstance()->get($redis_key);
                //删掉客服ID与用户fd的绑定
                RedisCache::getInstance()->del($redis_key);
                $redis_key = RedisCache::USER_FD_AND_ADMIN_ID . $user_fd;
                //删掉用户fd与客服ID的绑定
                RedisCache::getInstance()->del($redis_key);
                $ServerManager = ServerManager::getInstance()->getSwooleServer();
                //发送消息提示用户客服已断开连接
                $ServerManager->push($user_fd, json_encode(['type' => 0, 'msg' => '系统提示：客服网络已断开，请刷新页面重新链接~']));
            }
        }
        
        //解除客服fd绑定客服ID
        $redis_key = RedisCache::CS_ADMIN_FD . $fd;
        $res = RedisCache::getInstance()->exists($redis_key);
        if ($res) {
            $admin_id = RedisCache::getInstance()->get($redis_key);
            RedisCache::getInstance()->del($redis_key);
            //解除客服ID绑定客服fd及状态(前提更具客服fd数量)
            $redis_key = RedisCache::CS_ADMIN . $admin_id;
            $redis_field_1 = RedisCache::CS_ADMIN_FIELD_FD;
            $redis_field_2 = RedisCache::CS_ADMIN_FIELD_STATUS;
            $admin_fds = RedisCache::getInstance()->hGet($redis_key, $redis_field_1);
            $admin_fds = isset($admin_fds) ? json_decode($admin_fds, true) : [];
            if ($admin_fds) {
                if (count($admin_fds) == 1) {
                    RedisCache::getInstance()->del($redis_key);
                } else {
                    $admin_fds = json_encode(array_diff($admin_fds, [$fd]));
                    RedisCache::getInstance()->hSet($redis_key, $redis_field_1, $admin_fds);
                }
            }
        }
    }

    /**
     * 自定义握手事件
     * 在这里自定义验证规则
     * @param  swoole_http_request $request swoole http request
     * @param  swoole_http_response $response swoole http response
     * @return bool                         是否通过握手
     */
    protected function customHandShake(\swoole_http_request $request, \swoole_http_response $response): bool
    {
        $headers = $request->header;
        $cookie = $request->cookie;

        // if (如果不满足我某些自定义的需求条件，返回false，握手失败) {
        //    return false;
        // }
        return true;
    }

    /**
     * RFC规范中的WebSocket握手验证过程
     * @param  swoole_http_request $request swoole http request
     * @param  swoole_http_response $response swoole http response
     * @return bool                           是否通过验证
     */
    protected function secWebsocketAccept(\swoole_http_request $request, \swoole_http_response $response): bool
    {
        // ws rfc 规范中约定的验证过程
        if (!isset($request->header['sec-websocket-key'])) {
            // 需要 Sec-WebSocket-Key 如果没有拒绝握手
            var_dump('shake fai1 3');
            return false;
        }
        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $request->header['sec-websocket-key'])
            || 16 !== strlen(base64_decode($request->header['sec-websocket-key']))
        ) {
            //不接受握手
            var_dump('shake fai1 4');
            return false;
        }

        $key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $headers = array(
            'Upgrade'               => 'websocket',
            'Connection'            => 'Upgrade',
            'Sec-WebSocket-Accept'  => $key,
            'Sec-WebSocket-Version' => '13',
            'KeepAlive'             => 'off',
        );

        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        // 发送验证后的header
        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }
        return true;
    }
}
