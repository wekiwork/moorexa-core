<?php
namespace Lightroom\Vendor\Monolog;

use Monolog\Logger;
use Lightroom\Vendor\Monolog\MonologLevels;
use Lightroom\Common\Interfaces\LoggerWrapperInterface;
use Monolog\Handler\StreamHandler;

/**
 * @package Monolog wrapper
 * @author Amadi ifeanyi <amadiify.com>
 */
class MonologWrapper implements LoggerWrapperInterface
{
    use MonologLevels;

    // @var string logger name
    private $loggerName = 'Runtime';

    // logger channel
    private $channel = null;

    // @var string log path
    private $logpath = null;

    // @var logger handlers
    private $loggerHandlers = [];

    /**
     * @method MonologWrapper constructor
     */
    public function __construct()
    {
        // create a log channel
        $this->channel = $this->createLogChannel();
    }

    /**
     * @method MonologWrapper has handler
     */
    public function hasHandler(string $type) : bool
    {
        $hashandler = false;

        if (isset($this->loggerHandlers[$type]))
        
            $hashandler = true;
        

        return $hashandler;
    }

    /**
     * @method create log channel
     */
    public function createLogChannel()
    {
        return new Logger($this->loggerName);
    }

    /**
     * @method MonologWrapper get log channel
     */
    public function getLogChannel()
    {
        return $this->channel;
    }

    /**
     * @method get log path
     */
    public function getPath(string $level) : string
    {
        // return path
        return func()->const('lab') . '/logs/'.$level.'.log';
    }

    /**
     * @method MonologWrapper change channel name
     */
    public function name(string $channelName) : LoggerWrapperInterface
    {
        $this->channel = $this->channel->withName($channelName);

        return $this;
    }

    /**
     * @method MonologWrapper pushHandler
     */
    public function pushHandler(string $logType, $constant)
    {
        $path = $this->getPath($logType);

        // handler
        $handler = isset($this->loggerHandlers[$logType]) ? $this->loggerHandlers[$logType] : null;

        // Push stream if not added previously
        if (!$this->hasHandler($logType))
        
            // get handler
            $handler = $this->channel->pushHandler((new StreamHandler($path, $constant)));

            // save handler
            $this->loggerHandlers[$logType] = $handler;
        

        return $handler;
    }
}