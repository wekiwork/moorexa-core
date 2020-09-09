<?php
namespace Lightroom\Socket\Interfaces;
/**
 * @package Socket Listener Interface
 * @author Ammadi Ifeanyi <amadiify.com>
 */
interface SocketListenerInterface
{
    /**
     * @method SocketListenerInterface emit
     * @param string $event
     * @param array $config
     * This would emit an event from the socket server when something happens
     */
    public static function emit(string $event, array $config = []);

    /**
     * @method SocketListenerInterface events
     * This method should register all your events to be listened for by the emit method
     * @return array
     */
    public static function events() : array;
}