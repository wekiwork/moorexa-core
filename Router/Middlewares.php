<?php
namespace Lightroom\Router;

use Closure;
use Lightroom\Exceptions\{
    ClassNotFound, InterfaceNotFound
};
use Lightroom\Router\Interfaces\MiddlewareInterface;
use ReflectionException;

/**
 * @package Middlewares
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Middlewares
{
    /**
     * @var array $registeredMiddlewares
     */
    private static $registeredMiddlewares = [];

    /**
     * @method Middlewares registerMiddleware
     * @param Interfaces\MiddlewareInterface $instance
     * @param array $request
     * @return void
     */
    public static function registerMiddleware(Interfaces\MiddlewareInterface $instance, array $request) : void
    {
        self::$registeredMiddlewares[implode('/', $request)] = $instance;
    }

    /**
     * @method Middlewares loadMiddleware
     * @param string $middleware
     * @param array $request
     * @return void
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public static function loadMiddleware(string $middleware, array $request) : void 
    {
        // throw class not found exception
        if (!class_exists($middleware)) throw new ClassNotFound($middleware);

        // create reflection class
        $reflection = new \ReflectionClass($middleware);

        // check for implementation of MiddlewareInterface
        if (!$reflection->implementsInterface(MiddlewareInterface::class)) throw new InterfaceNotFound($middleware, MiddlewareInterface::class);

        // get instance without constructor
        $instance = $reflection->newInstanceWithoutConstructor();

        // load middleware
        Middlewares::registerMiddleware($instance, $request);
    }

    /**
     * @method Middlewares callLoadedMiddleware
     * @param array $request
     * @return bool
     * 
     * This method calls a loaded middleware by request array
     */
    public static function callLoadedMiddleware(array $request) : bool
    {
        // @var string $request
        $request = implode('/', $request);

        // @var bool $loaded
        $loaded = true;

        // check if request exists
        if (isset(self::$registeredMiddlewares[$request])) :

            // updated $loaded
            $loaded = false;

            // build request closure
            $requestClosure = function() use (&$loaded) { $loaded = true; };

            // get instance
            $instance = self::$registeredMiddlewares[$request];

            // call request method
            $instance->request($requestClosure);

            // call request closed
            if ($loaded) $instance->requestClosed();

        endif;

        // return bool
        return $loaded;
    }

    /**
     * @method Middlewares apply
     * @param string $middleware
     * @param array $request
     * @param Closure $callback 
     * @return bool
     * 
     * This method would load a middleware, call loaded middleware by request array and execute callback function
     */
    public static function apply(string $middleware, array $request, Closure $callback = null) : bool
    {
        // append middleware to request
        $request[] = $middleware;

        // load middleware
        self::loadMiddleware($middleware, $request);

        // call loaded middleware
        $load = self::callLoadedMiddleware($request);

        // load callback
        if ($load && $callback !== null) :

            // remove last
            array_pop($request);

            // load callback
            call_user_func($callback, $request);

        endif;

        // return bool
        return $load;

    }
}