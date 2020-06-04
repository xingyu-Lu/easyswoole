<?php
namespace App\Command;

use EasySwoole\EasySwoole\Command\CommandInterface;
use EasySwoole\EasySwoole\Command\Utility;

class Spider implements CommandInterface
{
    public function commandName(): string
    {
        return 'Spider';
    }

    public function exec(array $args): ?string
    {
        echo 'spider'.PHP_EOL;

        return null;
    }

    public function help(array $args): ?string
    {
        //输出logo
        $logo = Utility::easySwooleLog();
        return $logo."this is spider";
    }
}