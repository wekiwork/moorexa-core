<?php
namespace Lightroom\Socket;

use Closure;
use Lightroom\Socket\Interfaces\SocketListenerInterface;
/**
 * @package SocketHelper
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * An helper trait for our socket handlers
 */
trait SocketHelper
{
    /**
     * @var int $port 
     * This is the default port
     */
    public static $port = 2021;

    // @var SplObjectStorage $clients
    protected static $clients;

    /**
     * @method WorkerManSocketHandler __construct
     * This method creates a storage container for our clients
     */
    public function __construct()
    {
        self::$clients = new \SplObjectStorage;
    }
    
    /**
     * @method SocketHelper loadContext
     * @return array
     */
    private static function loadContext() : array 
    {
        // @var array $context 
        $context = [];

        // load activeHandler
        $activeHandler = $GLOBALS['activeHandler'];

        // load context
        if (isset($_ENV['socket']) && isset($_ENV['socket'][$activeHandler]['context'])) :

            // check and assign if it's an array
            if (is_array($_ENV['socket'][$activeHandler]['context'])) $context = $_ENV['socket'][$activeHandler]['context'];

        endif;

        // return context
        return $context;
    }

    /**
     * @method SocketHelper getPortFromEnv
     * This is an helper method and would try to replace the default port if a port has been
     * configured for socket in our Environment
     */
    private static function getPortFromEnv()
    {
        // load activeHandler
        $activeHandler = $GLOBALS['activeHandler'];

        // get port
        if (isset($_ENV['socket'])) :

            if (isset($_ENV['socket']['handlers'][$activeHandler]['port'])) :

                // load port
                self::$port = $_ENV['socket']['handlers'][$activeHandler]['port'];

            elseif (isset($_ENV['socket']['port'])) :

                // load port
                self::$port = $_ENV['socket']['port'];

            endif;
            
        endif;

        // echo port to screen
        echo PHP_EOL . 'Running on port ' . self::$port . PHP_EOL;
    }

    /**
     * @method SocketHelper loadEvents
     * @param Closure $callback
     * @return void
     */
    public static function loadEvents(Closure $callback) : void 
    {
        // load event classes
        $eventClasses = (isset($_ENV['socket']) && isset($_ENV['socket']['listeners'])) ? $_ENV['socket']['listeners'] : [];
        
        // load activeHandler
        $activeHandler = $GLOBALS['activeHandler'];

        // load listeners
        if (isset($_ENV['socket']) && isset($_ENV['socket']['handlers'][$activeHandler])) :
            
            // get handler
            $handler = $_ENV['socket']['handlers'][$activeHandler];

            // has listeners
            if (is_array($handler) && isset($handler['listeners'])) $eventClasses = $handler['listeners'];

        endif;

        // can we continue
        foreach ($eventClasses as $eventClass) :

            // class does not exists
            if (!class_exists($eventClass)) echo 'Event Class !{'.$eventClass.'} does not exists.';

            // class exists
            if (class_exists($eventClass)) :

                // load reflection instance
                $reflection = new \ReflectionClass($eventClass);

                // check if class does implements SocketListenerInterface
                if ($reflection->implementsInterface(SocketListenerInterface::class)) :

                    // get instance
                    $instance = $reflection->newInstanceWithoutConstructor();

                    // call the emit function
                    call_user_func($callback, $instance);

                else:
                    // this failed
                    echo 'Event Class !{'.$eventClass.'} does not implement ' . SocketListenerInterface::class . ' interface.';
                endif;

            endif;

        endforeach;
    }

    /**
     * @method SocketHelper getPortFromEnv
     * This is an helper method and would try to replace the default port if a port has been
     * configured for socket in our Environment
     * @return string
     */
    private static function getAddress() : string
    {
        // @var string $address
        $address = '0.0.0.0';

        // load activeHandler
        $activeHandler = $GLOBALS['activeHandler'];

        // get port
        if (isset($_ENV['socket'])) :

            if (isset($_ENV['socket']['handlers'][$activeHandler]['address'])) :

                // load address
                $address = $_ENV['socket']['handlers'][$activeHandler]['address'];

            elseif (isset($_ENV['socket']['address'])) :

                // load address
                $address = $_ENV['socket']['address'];

            endif;

        endif;

        // echo address to screen
        echo PHP_EOL . 'Listening on address '. 'ws://' . $address .':' . self::$port . PHP_EOL;

        // replace '0.0.0.0' with localhost
        if ($address == '0.0.0.0') :

            // echo address to screen
            echo PHP_EOL . 'Or try address '. 'ws://localhost' . ':' . self::$port . PHP_EOL;

        endif;

        // return string
        return $address;
    }
}