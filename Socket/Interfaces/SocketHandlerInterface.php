<?php
namespace Lightroom\Socket\Interfaces;
/**
 * @package Socket Handler Interface
 * @author Ammadi Ifeanyi <amadiify.com>
 */
interface SocketHandlerInterface
{
    /**
     * @method SocketHandlerInterface startServer
     * This would start the web socket server
     */
    public static function startServer();

    /**
     * @method SocketHandlerInterface triggerEvent
     * @param string $event
     * @param mixed $connection
     * @param mixed $data
     * @return void
     */
    public static function triggerEvent(string $event, $connection, $data=null) : void;
}