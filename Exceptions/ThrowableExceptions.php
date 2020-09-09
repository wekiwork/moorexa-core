<?php
namespace Lightroom\Exceptions;

use Exception;
use Lightroom\Core\ThrowableExceptionManager;
use ReflectionException;

/**
 * @package Throwable Exceptions
 * @author fregatelab <fregatelab.com>
 * 
 * Receives all errors thrown by the framework
 */
class ThrowableExceptions extends Exception
{
    use ThrowableExceptionManager;

    /**
     * @var array $throwableEvents
     */
    private static $throwableEvents = [];


    /**
     * @method ThrowableExceptions __construct
     * @param mixed $exception
     * @throws ReflectionException
     */
    public function __construct($exception)
    {
        // update class
        $this->message = $exception->getMessage();
        $this->line = $exception->getLine();
        $this->file = $exception->getFile();
        $this->code = $exception->getCode();

        // trigger event
        $this->callRegisteredThrowable($exception);
    }

    /**
     * @method ThrowableExceptions setThrowableEvent
     * @param string $classname
     */
    public static function setThrowableEvent(string $classname)
    {
        self::$throwableEvents[] = $classname;
    }

    /**
     * @method ThrowableExceptions getThrowableEvents
     * @return array
     */
    public static function getThrowableEvents() : array
    {
        return self::$throwableEvents;
    }
}