<?php
namespace Lightroom\Vendor\Monolog;
use Monolog\Logger;


trait MonologLevels
{
    /**
     * @method warning log
     */
    public function warning(string $message, array $data=[])
    {
        $this->pushHandler('warning', Logger::WARNING)->warning($message, $data);
    }

    /**
     * @method info log
     */
    public function info(string $message, array $data=[])
    {
        $this->pushHandler('info', Logger::INFO)->info($message, $data);
    }

    /**
     * @method debug log
     */
    public function debug(string $message, array $data=[])
    {
        $this->pushHandler('debug', Logger::DEBUG)->debug($message, $data);
    }

    /**
     * @method notice log
     */
    public function notice(string $message, array $data=[])
    {
        $this->pushHandler('notice', Logger::NOTICE)->notice($message, $data);
    }

    /**
     * @method error log
     */
    public function error(string $message, array $data=[])
    {
        $this->pushHandler('error', Logger::ERROR)->error($message, $data);
    }
    
    /**
     * @method critical log
     */
    public function critical(string $message, array $data=[])
    {
        $this->pushHandler('critical', Logger::CRITICAL)->critical($message, $data);
    }

    /**
     * @method alert log
     */
    public function alert(string $message, array $data=[])
    {
        $this->pushHandler('alert', Logger::ALERT)->alert($message, $data);
    }

    /**
     * @method emergency log
     */
    public function emergency(string $message, array $data=[])
    {
        $this->pushHandler('emergency', Logger::EMERGENCY)->emergency($message, $data);
    }
}
