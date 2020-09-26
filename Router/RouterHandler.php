<?php
namespace Lightroom\Router;

use Lightroom\Exceptions\{ClassNotFound, InterfaceNotFound, RequestManagerException};
use Closure;
use ReflectionException;
use function Lightroom\Requests\Functions\server;
/**
 * @package Router Handler
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This package registers the default router handler
 */
class RouterHandler
{
    /**
     * @var RouterHandler $registeredHandler
     */
    private static $registeredHandler;

    /**
     * @var RouterHandler $routeFound
     */
    private static $routeFound = false;

    /**
     * @var RouterHandler $defaultStarterPack
     */
    private static $defaultStarterPack = [];


    /**
     * @method RouterHandler constructor
     * @param string $handler
     *
     * This method registers an handler if it hasn't been registered previously
     * @param Closure $callback
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public function __construct(string $handler, Closure $callback)
    {
        if (self::$registeredHandler === null) :

            // ensure class exists
            if (!class_exists($handler)) throw new ClassNotFound($handler);

            // create reflection class
            $reflection = new \ReflectionClass($handler);

            // ensure class implements Interfaces\RouterHandlerInterface
            if (!$reflection->implementsInterface(Interfaces\RouterHandlerInterface::class)) :

                // interface not found..
                 throw new InterfaceNotFound($handler, Interfaces\RouterHandlerInterface::class);
                 
            endif;

            // include functions
            if (file_exists(__DIR__ . '/Functions.php')) include_once __DIR__ . '/Functions.php';

            // call callback function
            call_user_func($callback->bindTo($this));

            // open controller
            call_user_func([$handler, 'openController']);

        endif;
    }

    /**
     * @method RouterHandler configureStarterPack
     * @param string $starterTitle
     * @param array $starterConfiguration
     * @return void
     */
    public function configureStarterPack(string $starterTitle, array $starterConfiguration) : void 
    {
        // add starter title
        $starterConfiguration['title'] = $starterTitle;

        // register starter pack
        self::$defaultStarterPack[self::$registeredHandler] = $starterConfiguration;
    }

    /**
     * @method RouterHandler getStarterPack
     * @param string $target
     * @return mixed
     */
    public static function getStarterPack(string $target)
    {
        // @var null $returnValue
        $returnValue = null;

        // @var array $starterPack
        $starterPack = isset(self::$defaultStarterPack[self::$registeredHandler]) ? self::$defaultStarterPack[self::$registeredHandler] : null;

        // get starter configuration for handler
        if ($starterPack !== null) :

            // check if $starterPack has target
            if (isset($starterPack[$target])) :

                // update $returnValue
                $returnValue = $starterPack[$target];

            endif;

        endif;

        // return mixed
        return $returnValue;
    }

    /**
     * @method RouterHandler routeNotFound
     * @return bool
     */
    public static function routeNotFound() : bool 
    {
        return (self::$routeFound == true) ? false : true;
    }

    /**
     * @method RouterHandler routeFound
     * @return void
     * 
     * Disallow further routing mechanism
     */
    public static function routeFound() : void 
    {
        self::$routeFound == true;
    }

    /**
     * @method RouterHandler resetRouter
     * @return bool
     */
    public static function resetRouter() : void 
    {
        self::$routeFound == false;
    }
}