<?php
namespace App\Spider;

use App\Cache\RedisCache;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\HttpClient\HttpClient;
use EasySwoole\Utility\File;
use EasySwoole\Utility\Random;
use Swoole\Coroutine;

class ConsumeProcess extends AbstractProcess
{

    protected function run($arg)
    {
        // TODO: Implement consume() method.
        for ($i=0; $i < $arg['consumeCoroutineNum']; $i++) { 
            Coroutine::create(function () {
                while (1) {
                    $data = RedisCache::getInstance()->pop(RedisCache::SPIDER_CONSUME_QUEUE_KEY);
                    if (empty($data)) {
                        Logger::getInstance()->console(RedisCache::SPIDER_CONSUME_QUEUE_KEY.' 暂无可消费队列');
                        sleep(1);
                        continue;
                    }
                    $data = json_decode($data,true);
                    Logger::getInstance()->console(RedisCache::SPIDER_CONSUME_QUEUE_KEY.' 正在处理 '.$data['alt']);
                    $pathInfo = pathinfo($data['src']);
                    $path = EASYSWOOLE_ROOT.'/Images/'.date('Y-m-d').'/'.str_replace('/', '', $data['alt']) . Random::character(3) .'.'.$pathInfo['extension'];
                    $httpClient = new HttpClient($data['src']);
                    File::createFile($path,'');
                    $res = $httpClient->download($path);
                    if ($res && $res->getErrCode() == 0) {
                        Logger::getInstance()->console('下载成功 ' . $data['src']);
                    } else {
                        Logger::getInstance()->console('下载失败' . $data['src']);
                    }
                }
            });
        }
    }

    /*
     * 该回调可选
     * 当该进程退出的时候，会执行该回调
     */
    protected function onShutDown()
    {
        Logger::getInstance()->console('ConsumeProcess exit');
    }
}