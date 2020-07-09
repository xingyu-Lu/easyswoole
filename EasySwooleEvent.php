<?php
namespace EasySwoole\EasySwoole;


use App\Crontab\Test;
use App\Spider\ConsumeProcess;
use App\Spider\ProductProcess;
use App\WebSocket\WebSocketEvent;
use App\WebSocket\WebSocketParser;
use EasySwoole\Component\Process\Manager;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Crontab\Crontab;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Db\Config as DbConfig;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Redis\Config\RedisConfig;
use EasySwoole\Socket\Dispatcher;
use Swoole\Coroutine;

class EasySwooleEvent implements Event
{

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');
        Coroutine::set(['hook_flags'=> SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL]);

        //注册Mysql连接池
        $dbData = Config::getInstance()->getConf('MYSQL');
        $dbConfig = new DbConfig($dbData);
        DbManager::getInstance()->addConnection(new Connection($dbConfig));

        //注册redis连接池
        $redisData = Config::getInstance()->getConf('REDIS');
        $redisConfig = new RedisConfig($redisData);
        Redis::getInstance()->register('redis',$redisConfig);
    }

    public static function mainServerCreate(EventRegister $register)
    {
        // TODO: Implement mainServerCreate() method.
        /**
         * **************** websocket控制器 **********************
         */
        // 创建一个 Dispatcher 配置
        $conf = new \EasySwoole\Socket\Config();
        // 设置 Dispatcher 为 WebSocket 模式
        $conf->setType(\EasySwoole\Socket\Config::WEB_SOCKET);
        // 设置解析器对象
        $conf->setParser(new WebSocketParser());
        // 创建 Dispatcher 对象 并注入 config 对象
        $dispatch = new Dispatcher($conf);
        // 给server 注册相关事件 在 WebSocket 模式下  on message 事件必须注册 并且交给 Dispatcher 对象处理
        $register->set(EventRegister::onMessage, function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) use ($dispatch) {
            $dispatch->dispatch($server, $frame->data, $frame);
        });
        //自定义握手
        $websocketEvent = new WebSocketEvent();
        // $register->set(EventRegister::onHandShake, function (\swoole_http_request $request, \swoole_http_response $response) use ($websocketEvent) {
        //     $websocketEvent->onHandShake($request, $response);
        // });
        $register->add(EventRegister::onOpen, function (\swoole_websocket_server $server, \swoole_http_request $request) use ($websocketEvent) {
            $websocketEvent->onOpen($server, $request);
        });
        $register->add(EventRegister::onClose, function (\swoole_server $server, int $fd, int $reactorId) use ($websocketEvent) {
            $websocketEvent->onClose($server, $fd, $reactorId);
        });
        $register->add(EventRegister::onWorkerStart, function (\Swoole\Server $server, int $workerId) {
            // include_once __DIR__ . '/env.php';
            // 查看worker启动前已加载的文件
            // var_dump(get_included_files());
            // 连接预热
            Redis::getInstance()->get('redis')->keepMin();
            DbManager::getInstance()->getConnection()->getClientPool()->keepMin();
        });

        /**
         * **************** 注册spider进程 **********************
         */
        /*for ($i=0; $i < 2; $i++) { 
            $processConfig= new \EasySwoole\Component\Process\Config();
            switch ($i) {
                case 0:
                    $processConfig->setProcessName('productProcess');
                    break;
                
                default:
                    $processConfig->setProcessName('consumeProcess');
                    break;
            }
            $processConfig->setProcessGroup('Spider');
            $processConfig->setArg([
                'url' => 'http://www.netbian.com/meinv/index_198.htm',
                'productCoroutineNum' => 3,
                'consumeCoroutineNum' => 3
            ]);
            $processConfig->setEnableCoroutine(true);
            switch ($i) {
                case 0:
                    Manager::getInstance()->addProcess(new ProductProcess($processConfig));
                    break;
                
                default:
                    Manager::getInstance()->addProcess(new ConsumeProcess($processConfig));
                    break;
            }
            unset($processConfig);
        }*/
        
        /**
         * **************** Crontab任务计划 **********************
         */
        // Crontab::getInstance()->addTask(Test::class);
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }
}