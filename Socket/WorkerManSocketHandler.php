<?php
namespace Lightroom\Socket;

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Lightroom\Socket\Interfaces\{SocketHandlerInterface, SocketListenerInterface};
/**
 * @package WorkerMan Socket Hander
 * @author Amadi Ifeanyi <amadiify.com>
 */
class WorkerManSocketHandler implements SocketHandlerInterface
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

        // open socket handler
        $io = new SocketIO(self::$port, self::loadContext());

        $io->on('connection', function ($connection) use ($io) {

            echo "New connection\n";

            // emit event
            WorkerManSocketHandler::triggerEvent('connected', $connection, $io);
        });

        Worker::runAll();
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
    
}