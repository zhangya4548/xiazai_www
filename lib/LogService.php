<?php
namespace Lib;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * 封装方法调用老框架 yii_api接口
 * Class ApiService
 * @package App\Services
 */
class LogService
{
    public $log;
    public function __construct()
    {
        $this->log = new Logger('name');
        $this->log->pushHandler(new StreamHandler('log.log', Logger::WARNING));
    }

    public function warning($msg,$msgArr = [])
    {
        $this->log->warning($msg,$msgArr);
    }

}
