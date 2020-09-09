<?php
namespace Lightroom\Core;

use Exception;
use Lightroom\Exceptions\{
    Interfaces\ThrowableExceptionInterface, ThrowableExceptions as Throwable
};
use ReflectionException;

/**
 * @package ThrowableExceptionManager trait
 * @author Amadi ifeanyi
 */
trait ThrowableExceptionManager
{
    /**
     * @method ThrowableExceptionManager registerThrowable
     * Registers a class that implements ThrowableExceptionInterface
     * @return void
     * @param ThrowableExceptionInterface $instance
     */
    final public function registerThrowable(ThrowableExceptionInterface $instance)
    {
        // register class name only
        Throwable::setThrowableEvent(get_class($instance));
    }

    /**
     * @method ThrowableExceptionManager callRegisteredThrowable
     * Call all registered throwable.
     * @param Exception $exception reference
     * @return void
     * @throws ReflectionException
     */
    final private function callRegisteredThrowable(&$exception)
    {
        // get throwable
        $throwable = Throwable::getThrowableEvents();

        // check size and run a loop
        if (count($throwable) > 0) :
        
            foreach ($throwable as $className) :

                // get class instance without constructor
                $reflection = new \ReflectionClass($className);

                // get instance
                $instance = $reflection->newInstanceWithoutConstructor();

                // call throwableFired
                $instance->throwableFired($exception);

            endforeach;

            // clean up
            unset($className, $throwable, $reflection);

        endif;
    }
}