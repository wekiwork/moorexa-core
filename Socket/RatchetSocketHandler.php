<?php
namespace Lightroom\Socket;

use Closure;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Lightroom\Socket\Interfaces\{SocketHandlerInterface, SocketListenerInterface};
/**
 * @package RatchetSocketHandler
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is the default Ratchet socket handler, which would help trigger events that can be handled by 
 * multiple event listeners.
 */
class RatchetSocketHandler implements SocketHandlerInterface, MessageComponentInterface
{
    use SocketHelper;

    /**
     * @method SocketHandlerInterface startServer
     * This would start the web socket server
     */
    public static function startServer()
    {
        // load port from env
        self::getPortFromEnv();

        // get address
        $address = self::getAddress();

        // build server and run
        IoServer::factory( new HttpServer( new WsServer( new RatchetSocketHandler() )), self::$port, $address )->run();
    }

    /**
     * @method SocketHandlerInterface triggerEvent
     * @param string $event
     * @param mixed $connection
     * @param mixed $data
     * @return void
     */
    public static function triggerEvent(string $event, $connection, $data=null) : void
    {
        // load events
        self::loadEvents(function($eventClass) use (&$event, &$connection, &$data){

            // trigger event class
            $eventClass->emit($event, [

                // current connected user
                'connection' => &$connection,

                // all connected users
                'connections' => &self::$clients,

                // data sent
                'data' => $data
            ]);
        });
    }

    /**
     * @method MessageComponentInterface onOpen
     * @param ConnectionInterface $conn
     * 
     * This would trigger when a new connection has been requested.
     * For now, we just add connection to our client container, trigger connected event 
     */
    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        self::$clients->attach($conn);

        // we have a new socket connection
        echo "New connection! ({$conn->resourceId})\n";

        // trigger connected event
        self::triggerEvent('connected', $conn);
    }

    /**
     * @method MessageComponentInterface onClose
     * @param ConnectionInterface $conn
     * 
     * This would trigger when a connection has been closed. Usually happens when a client reloads his/her browser 
     * or switch to another tab
     */
    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        self::$clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";

        // trigger disconnected event
        self::triggerEvent('disconnected', $conn);
    }

    /**
     * @method MessageComponentInterface onError
     * @param ConnectionInterface $conn
     * @param \Exception $e
     * 
     * This would triggger when an error occured. We would just trigger the error event
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // trigger error
        self::triggerEvent('error', $conn, "An error has occurred: {$e->getMessage()}\n");
    }

    /**
     * @method MessageComponentInterface onMessage
     * @param ConnectionInterface $conn
     * @param mixed $data 
     * 
     * This would trigger when a client sends a message 
     */
    public function onMessage(ConnectionInterface $conn, $data)
    {
        // trigger new message event
        self::triggerEvent('new message', $conn, $data);

        // handle specific request if event passed as a json data
        if (is_string($data) && (strpos($data, '{') !== false)) :

            // load json data
            $jsonData = json_decode($data);

            // check for event
            if (isset($jsonData->event)) :

                // trigger event
                self::triggerEvent($jsonData->event, $conn, $jsonData);

            endif;

        endif;
    }

}