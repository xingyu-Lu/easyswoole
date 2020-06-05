<?php


namespace App\HttpController;


use EasySwoole\Component\Context\ContextManager;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\FastCache\Cache;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\Message\Status;
use EasySwoole\ORM\DbManager;
use EasySwoole\Spider\SpiderClient;
use Swoole\Coroutine;

class Index extends Controller
{

	public function test()
	{
		// Coroutine::create(function () {
  //           echo "string";
  //           sleep(5);
  //           echo "aaa";
  //       });

        try {
            throw new \Exception("Error Processing Request", 1);
            
        } catch (\Throwable $e) {
            // var_dump($e, $e->message, $e->code, $e->file, $e->line, 'dada');
            // var_dump('adaaa');
            var_dump($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine());
        }

        return $this->writeJson(Status::CODE_OK, ['ss'], "success");


		// $words = [
  //           'php',
  //           'java',
  //           'go'
  //       ];

  //       foreach ($words as $word) {
  //           Cache::getInstance()->enQueue('SEARCH_WORDS', $word);
  //       }

  //       $wd = Cache::getInstance()->deQueue('SEARCH_WORDS');

  //       SpiderClient::getInstance()->addJob(
  //           'https://www.baidu.com/s?wd=php&pn=0',
  //           [
  //               'page' => 1,
  //               'word' => $wd
  //           ]
  //       );
	}

    public function index()
    {
        $result = [];
        $wait->add();
        Coroutine::create(function () use ($wait) {
            Coroutine::defer(function () {
                var_dump('defer');
            });
            $wait->done();
        });

        $wait->add();
        Coroutine::create(function () use ($wait, &$result) {
            $url = 'https://api.appems.com/api/site/testapi';
            $ch = curl_init();  
            curl_setopt($ch, CURLOPT_URL, $url);  
            curl_setopt($ch, CURLOPT_HEADER, false);  
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result[] = json_decode(curl_exec($ch), true);  
            curl_close($ch);
            $wait->done();
        });

        $wait->add();
        Coroutine::create(function () use ($wait, &$result) {
            $url = 'https://www.bad-boys.top/v1/default/index';
            $ch = curl_init();  
            curl_setopt($ch, CURLOPT_URL, $url);  
            curl_setopt($ch, CURLOPT_HEADER, false);  
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result[] = json_decode(curl_exec($ch), true);  
            curl_close($ch);
            $wait->done();
        });

        $wait->add();
        Coroutine::create(function () use ($wait, &$result) {
            $url = 'https://api.appems.com/api/callback/notice';
            $ch = curl_init();  
            curl_setopt($ch, CURLOPT_URL, $url);  
            curl_setopt($ch, CURLOPT_HEADER, false);  
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result[] = json_decode(curl_exec($ch), true);  
            curl_close($ch);
            $wait->done();
        });

        $wait->wait();

        return $this->writeJson(Status::CODE_OK, $result, "success");
        Coroutine::create(function () {
            $url = 'https://www.bad-boys.top/v1/default/index';
            $ch = curl_init();  
            curl_setopt($ch, CURLOPT_URL, $url);  
            curl_setopt($ch, CURLOPT_HEADER, false);  
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result=curl_exec($ch);  
            curl_close($ch);
            return $this->writeJson(Status::CODE_OK, ['ss'], "success");
        });

        return $this->writeJson(Status::CODE_OK, ['ss'], "success");

        // $arr = [\EasySwoole\EasySwoole\Config::getInstance()->getConf(), $this->request()->getRequestParam(), $_SERVER, $_GET, $_POST, $_FILES, $_REQUEST];
        // return $this->writeJson(Status::CODE_OK, $arr, "success");
        // $file = EASYSWOOLE_ROOT.'/vendor/easyswoole/easyswoole/src/Resource/Http/welcome.html';
        // if(!is_file($file)){
        //     $file = EASYSWOOLE_ROOT.'/src/Resource/Http/welcome.html';
        // }
        // $this->response()->write(file_get_contents($file));
    }

    protected function actionNotFound(?string $action)
    {
        $this->response()->withStatus(404);
        $file = EASYSWOOLE_ROOT.'/vendor/easyswoole/easyswoole/src/Resource/Http/404.html';
        if(!is_file($file)){
            $file = EASYSWOOLE_ROOT.'/src/Resource/Http/404.html';
        }
        $this->response()->write(file_get_contents($file));
    }
}