<?php
namespace Lightroom\Exceptions;

use Lightroom\Exceptions\Interfaces\ThrowableExceptionInterface;
use Lightroom\Common\Interfaces\{ExceptionHandlerInterface, ExceptionWrapperInterface};
use Lightroom\Adapter\Errors\FrameworkDefault;
use ReflectionException;

/**
 * @package ExceptionHandler 
 * @author Amadi Ifeanyi
 * 
 * This package registers a default exception handler. It would be triggered when a throwable event occurs.
 */

class ExceptionHandler implements ThrowableExceptionInterface
{
    /**
     * @var ExceptionHandlerInterface $handler
     */
    private static $handler;

    /**
     * @method ThrowableExceptionInterface throwableFired
     * @param mixed $exception reference
     * @return void
     * @throws FrameworkDefault
     * @throws ReflectionException
     */
    public function throwableFired(&$exception)
    {
        // call handler
        self::callHandler();
    }

    /**
     * @method ExceptionHandler setHandler
     * sets a default handler
     * @param ExceptionHandlerInterface $handler
     * @return void
     */
    public static function setHandler(ExceptionHandlerInterface $handler) : void
    {
        // set handler
        self::$handler = $handler;
    }

    /**
     * @method ExceptionHandler callHandler
     * call a default handler
     * @return void
     * @throws FrameworkDefault
     * @throws ReflectionException
     */
    public static function callHandler() : void
    {
        // get handler
        $handler = self::$handler;

        // get exception class
        $exceptionClass = $handler->getExceptionClass();

        // get exception method
        $exceptionMethod = $handler->getExceptionMethod();

        // check if class exists
        if (class_exists($exceptionClass)) :
        
            // check if class implements ExceptionWrapperInterface
            $reflection = new \ReflectionClass($exceptionClass);

            if ($reflection->implementsInterface(ExceptionWrapperInterface::class)) :
            
                // load exception class
                $instance = new $exceptionClass;

                // load exception method
                if (method_exists($instance, $exceptionMethod)) :
                
                    // save handler instance
                    $handler->saveHandlerInstance($instance);
                
                    call_user_func([$instance, $exceptionMethod]);

                else:
                    
                    throw new FrameworkDefault('Exception method #'.$exceptionMethod.' does not exists in ' . $exceptionClass);
                
                endif;

            else:

                throw new FrameworkDefault('Exception class #'.$exceptionClass.' does not implement interface ' . ExceptionWrapperInterface::class);
            
            endif;
        
        endif;
    }
}