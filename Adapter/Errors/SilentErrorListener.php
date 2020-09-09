<?php
namespace Lightroom\Adapter\Errors;

use Lightroom\Adapter\Interfaces\SilentErrorListenerInterface;
use Lightroom\Exceptions\ClassNotFound;
use Lightroom\Exceptions\InterfaceNotFound;
use ReflectionException;

/**
 * @package SilentErrorListener class
 * @author Amadi ifeanyi <amadiify.com>
 * 
 * This class opens up a channel for listening for errors thrown even after turning off error reporting from the bootcore engine.
 */
class SilentErrorListener
{
    /**
     * @var string $channel
     */
    private static $channel = '';


    /**
     * @method SilentErrorListener registerChannel
     * @param string $className
     * @return void
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public static function registerChannel(string $className) : void
    {
        // ensure class does exists
        if (class_exists($className)) :
                
            // create a reflection class
            $reflection = new \ReflectionClass($className);

            // does class implement SilentErrorListenerInterface
            if ($reflection->implementsInterface(SilentErrorListenerInterface::class)) :

                // register channel
                self::$channel = $className;

                // clean up
                unset($reflection, $className);

            else:

                throw new InterfaceNotFound($className, SilentErrorListenerInterface::class);
            
            endif;

        else :

            // throw class not found exception
            throw new ClassNotFound($className);
            
        endif;
    }

    /**
     * @method SilentErrorListener callChannelWith
     * @param Exception $exception
     * @return void
     * @throws ReflectionException
     */
    public static function callChannelWith($exception) : void
    {
        // check if channel is not empty
        if (self::$channel !== '') :

            // create a reflection class
            $reflection = new \ReflectionClass(self::$channel);

            // create instance without constructor
            $instance = $reflection->newInstanceWithoutConstructor();

            // call exceptionOccured method
            $instance->exceptionOccured($exception);

        endif;
    }
}