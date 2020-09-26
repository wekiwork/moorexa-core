<?php
namespace Lightroom\Queues;

use Closure;
use PhpAmqpLib\Message\AMQPMessage;
use Lightroom\Queues\QueueContainer;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Lightroom\Queues\Interfaces\CallbackInterface;
/**
 * @package QueueHandler
 * @author Amadi Ifeanyi <amadiify.com>
 */

class QueueHandler
{
    /**
     * @method QueueHandler sendTask
     * @param string $taskName
     * @param Closure $closureFunction
     */
    public static function sendTask(string $taskName, Closure $closureFunction)
    {
        // load configuration
        $configuration = (isset($_ENV['rabbitmq']) ? $_ENV['rabbitmq'] : null); 

        if ($configuration !== null) :

            // open connection
            $connection = new AMQPStreamConnection(
                $configuration['address'], 
                $configuration['port'], 
                $configuration['username'], 
                $configuration['password']
            );

            // load channel
            $channel = $connection->channel();

            // listen for jobs
            $channel->queue_declare($configuration['queueName'], false, true, false, false);

            // create class
            $class = new QueueContainer();
            $class->setJobName($taskName);
            $class->setJob($closureFunction);
            $class = serialize($class);
            
            // message to send
            $msg = new AMQPMessage($class, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));

            // publish message
            $channel->basic_publish($msg, '', $configuration['queueName']);

            // close connection
            $channel->close();
            $connection->close();

            // trigger event
            self::triggerEvent('taskSent', $taskName, $class);

        else:
            // could not find configuration
            throw new \Exception('Could not find rabbitmq configuration');
        endif;
    }

    /**
     * @method QueueHandler triggerEvent
     * @param string $eventName
     * @param string $taskName
     * @param mixed $taskBody
     */
    public static function triggerEvent(string $eventName, string $taskName, $taskBody)
    {   
        // load callback from configuration
        $configuration = (isset($_ENV['rabbitmq']) ? $_ENV['rabbitmq'] : null);

        if ($configuration !== null && isset($configuration['callback'])) :

            // get callback class
            $callbackClass = $configuration['callback'];

            // check length
            if (strlen($callbackClass) > 3) :

                // check if class exists
                if (!class_exists($callbackClass)) return logger()->error('QueueHandler callback class ' . $callbackClass . ' does not exists.');

                // load class
                $reflection = new \ReflectionClass($callbackClass);

                // check required interface
                if (!$reflection->implementsInterface(CallbackInterface::class)) return logger()->error('QueueHandler callback class ' . $callbackClass . ' did not implement required interface #{'.CallbackInterface::class.'}.');

                // create instance
                $callbackClass = new $callbackClass;

                // call method now
                call_user_func([$callbackClass, $eventName], $taskName, $taskBody);
                
            endif;

        endif;
    }
}