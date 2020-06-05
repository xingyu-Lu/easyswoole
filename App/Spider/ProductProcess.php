<?php
namespace App\Spider;

use App\Cache\RedisCache;
use EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\HttpClient\HttpClient;
use QL\QueryList;
use Swoole\Coroutine;

class ProductProcess extends AbstractProcess
{

    protected function run($arg)
    {
        // TODO: Implement consume() method.
        RedisCache::getInstance()->push(RedisCache::SPIDER_PRODUCT_QUEUE_KEY, $arg['url']);

        for ($i=0; $i < $arg['productCoroutineNum']; $i++) { 
            Coroutine::create(function () {
                while (1)
                {
                    //从队列中区出地址
                    $url = RedisCache::getInstance()->pop(RedisCache::SPIDER_PRODUCT_QUEUE_KEY);
                    if(empty($url)) {
                        Logger::getInstance()->console('暂无URL');
                        sleep(1);
                        continue;
                    }

                    Logger::getInstance()->console(RedisCache::SPIDER_PRODUCT_QUEUE_KEY . ' 正在处理 ' . $url);

                    // 通过http协程客户端拿到地址内容
                    $httpClient = new HttpClient($url);
                    $body = $httpClient->get()->getBody();
                    if (empty($body)) {
                        Logger::getInstance()->console('URL无内容');
                        sleep(1);
                        continue;
                    }

                    // 开始生产,根据内容，设置规则
                    libxml_use_internal_errors(true);
                    $body = iconv('GBK', 'UTF-8', $body);
                    $ql = QueryList::html($body);
                    $rules = [
                        'src' => ['img', 'src'],
                        'alt' => ['img', 'alt'],
                    ];
                    $nextUrl = $ql->find('.page .prev')->eq(1)->attr('href');
                    $imgList = $ql->rules($rules)->range('.list ul li a')->queryData();
                    foreach ($imgList as $key => $value) {
                        if (empty($value['src'])) {
                            continue;
                        }
                        RedisCache::getInstance()->push(RedisCache::SPIDER_CONSUME_QUEUE_KEY, json_encode($value));
                    }

                    //要爬取的页数,没有则停止生产
                    if(empty($nextUrl))
                    {
                        Logger::getInstance()->console('生产完成，待消费完成');
                        sleep(1);
                        continue;
                    }
                    Logger::getInstance()->console($nextUrl);

                    //页面中的爬取链接不带host，要拼接上
                    RedisCache::getInstance()->push(RedisCache::SPIDER_PRODUCT_QUEUE_KEY, 'http://www.netbian.com' . $nextUrl);
                    sleep(0.5);
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
        Logger::getInstance()->console('ProductProcess exit');
    }
}