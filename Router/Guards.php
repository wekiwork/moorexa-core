<?php
namespace Lightroom\Router;

use Lightroom\Exceptions\{
    ClassNotFound, InterfaceNotFound, MethodNotFound
};
use Lightroom\Router\Interfaces\{
    GuardInterface, RouteGuardInterface
};
use ReflectionException;

/**
 * @package Guards
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Guards
{
    /**
     * @var array $loadedGuards
     */
    private static $loadedGuards =[];

    /**
     * @var array $loadedRouteGuards
     */
    private static $loadedRouteGuards = [];

    /**
     * @var Guards guard magic method
     */
    const MAGIC_METHOD = 'guardInit';

    /**
     * @method Guards loadGuard
     * @param mixed ...$arguments
     * @return mixed
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws MethodNotFound
     * @throws ReflectionException
     */
    public static function loadGuard(...$arguments) 
    {
        // add method
        if (!isset($arguments[1])) $arguments[1] = self::MAGIC_METHOD;

        // get guard and method
        list($guard, $method) = $arguments;

        // get request
        $request = end($arguments);

        // @var GuardInterface $instance
        $instance = self::loadGuardInstance($guard);

        // update method
        if (!is_string($method)) $method = self::MAGIC_METHOD;

        // check if method exists
        if (!method_exists($instance, $method)) throw new MethodNotFound(get_class($instance), $method);

        // @var bool $hasRouteGuard
        $hasRouteGuard = self::hasRouteGuard($instance);

        // if instance implements route guard, then we set the incoming url
        if ($hasRouteGuard && is_array($request)) $instance->setIncomingUrl($request);

        // get arguments for method
        $arguments = $method != self::MAGIC_METHOD ? array_splice($arguments, 2) : array_splice($arguments, 1);

        // load guard
        call_user_func_array([$instance, $method], $arguments);
        
        // if instance implements route guard, then we return the incoming url
        if ($hasRouteGuard && is_array($request)) return $instance->getIncomingUrl();

        return null;
    }

    /**
     * @method Guards loadGuardInstance
     * @param string $guard
     * @return GuardInterface
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    private static function loadGuardInstance(string $guard) : GuardInterface
    {
        if (!isset(self::$loadedGuards[$guard])) : 

            // throw class not found exception
            if (!class_exists($guard)) throw new ClassNotFound($guard);

            // create reflection class
            $reflection = new \ReflectionClass($guard);

            // check for implementation of GuardInterface
            if (!$reflection->implementsInterface(GuardInterface::class)) throw new InterfaceNotFound($guard, GuardInterface::class);

            // check for implementation of RouteGuardInterface
            if ($reflection->implementsInterface(RouteGuardInterface::class)) :

                // add guard to loadedRouteGuards
                self::$loadedRouteGuards[] = $guard;

            endif;  

            // get instance without constructor
            self::$loadedGuards[$guard] = $reflection->newInstanceWithoutConstructor();

        endif;

        // return GuardInterface
        return self::$loadedGuards[$guard];
    }

    /**
     * @method Guards hasRouteGuard
     * @param GuardInterface $guard
     * @return bool
     */
    private static function hasRouteGuard(GuardInterface $guard) : bool
    {
        // get flipped loaded route guards
        $loadedRouteGuards = array_flip(self::$loadedRouteGuards);

        // @var bool $hasRouteGuard
        $hasRouteGuard = false;

        // update $hasRouteGuard
        if (isset($loadedRouteGuards[get_class($guard)])) $hasRouteGuard = true;

        // return bool
        return $hasRouteGuard;
    }
}

