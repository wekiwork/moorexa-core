<?php
namespace Lightroom\Common\Interfaces;

interface LoggerWrapperInterface
{
    /**
     * @method LoggerWrapperInterface get log path
     * @param string $logType
     * @return string
     */
    public function getPath(string $logType) : string;
    /**
     * @method LoggerWrapperInterface create log channel
     */
    public function createLogChannel();
    /**
     * @method LoggerWrapperInterface get log channel
     */
    public function getLogChannel();

    /**
     * @method LoggerWrapperInterface channel name
     * @param string $channelName
     * @return LoggerWrapperInterface
     */
    public function name(string $channelName) : LoggerWrapperInterface;

    /**
     * @method LoggerWrapperInterface warning log
     * @param string $message
     * @param array $data
     */
    public function warning(string $message, array $data);

    /**
     * @method LoggerWrapperInterface info log
     * @param string $message
     * @param array $data
     */
    public function info(string $message, array $data);

    /**
     * @method LoggerWrapperInterface debug log
     * @param string $message
     * @param array $data
     */
    public function debug(string $message, array $data);

    /**
     * @method LoggerWrapperInterface notice log
     * @param string $message
     * @param array $data
     */
    public function notice(string $message, array $data);

    /**
     * @method LoggerWrapperInterface error log
     * @param string $message
     * @param array $data
     */
    public function error(string $message, array $data);

    /**
     * @method LoggerWrapperInterface critical log
     * @param string $message
     * @param array $data
     */
    public function critical(string $message, array $data);

    /**
     * @method LoggerWrapperInterface alert log
     * @param string $message
     * @param array $data
     */
    public function alert(string $message, array $data);

    /**
     * @method LoggerWrapperInterface emergency log
     * @param string $message
     * @param array $data
     */
    public function emergency(string $message, array $data);
}