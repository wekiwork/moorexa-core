<?php
namespace Lightroom\Packager\Moorexa\MVC\Helpers;

use Lightroom\Adapter\ClassManager;
use Lightroom\Router\RouterHandler;
use Lightroom\Exceptions\ClassNotFound;
USE Lightroom\Packager\Moorexa\Helpers\URL;
/**
 * @package ControllerServeMethods
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait ControllerServeMethods
{   
    /**
     * @var string $basepath
     */
    private static $basePath = '';

    /**
     * @var string $mainController
     */
    private static $mainController = '';

    /**
     * @method ControllerServeMethods basePath
     * @return string
     * 
     * This method returns the controller base path
     */
    public static function basePath() : string 
    {
        return self::$basePath != '' ? self::$basePath : env('bootstrap', 'controller.basepath');
    }

    /**
     * @method ControllerServeMethods setBasePath
     * @param string $controller
     * @param string $directory
     * @return void
     */
    public static function setBasePath(string $controller, string $directory) : void 
    {
        // @var array $incomingUrl
        $incomingUrl = URL::getIncomingUri();

        // check controller
        if (strtoupper($controller) == strtoupper($incomingUrl[self::FIRST_PARAM])) 

            // set base path
            self::$basePath = $directory . '/';
    }

    /**
     * @method ControllerServeMethods loadControllerGuard
     * @return void
     *
     * This method loads the default controller guard
     * @throws ClassNotFound
     */
    private static function loadControllerGuard() : void
    {
        // @var closure $guard
        $guard = self::config('controller.guard');

        // @var array $incomingUrl
        $incomingUrl = URL::getIncomingUri();

        // set controller
        self::$mainController = $incomingUrl[0];

        // load closure function
        if (!is_null($guard) && is_callable($guard)) call_user_func_array($guard, [&$incomingUrl]);

        // set incoming url
        URL::setIncomingUri($incomingUrl);
    }

    /**
     * @method ControllerServeMethods loadViewGuard
     * @param string $view
     * @return void
     *
     * This method loads the default view guard
     * @throws ClassNotFound
     */
    private static function loadViewGuard(string $view) : void
    {
        // @var array $guard
        $guard = self::config('view.guard');

        // load guards
        if (!is_null($guard) && is_array($guard)) :
        
            // check list of views 
            foreach ($guard as $viewTarget => $closureCallback) :
            
                // convert viewTarget to array
                $viewTarget = explode('|', $viewTarget);

                foreach ($viewTarget as $target) :
                
                    if ($target == $view) :

                        // call closure function
                        call_user_func($closureCallback);

                        break;

                    endif;

                endforeach;

            endforeach;

        endif;
    }

    /**
     * @method ControllerServeMethods loadDefaultViewAndGuard
     * @param array $configuration
     * @return array
     * @throws ClassNotFound
     */
    private static function loadDefaultViewAndGuard(array $configuration) : array 
    { 
        // @var array $incomingUrl
        $incomingUrl = URL::getIncomingUri();
        
        // get controller
        $controller =& $incomingUrl[(int) self::FIRST_PARAM];

        // reload instance
        if (self::$mainController != $controller) :

            // @var string $controllerClass
            $controllerClass = self::getControllerClass($controller);

            // reload reflection class
            $configuration['reflection'] = new \ReflectionClass($controllerClass);

        endif;

        // get the view
        $view =& $incomingUrl[(int) self::SECOND_PARAM];

        // check to match if default view was used
        if ($view == $configuration['defaultView']) :
        
            // get default view
            $configView = self::config('default.view');

            // update view
            if ($configView !== null) $view = $configView;

            // load view from $defaultView property
            if (!$configuration['reflection']->hasMethod($view) && $configuration['reflection']->hasProperty('defaultView')) :
                
                // update view
                $view = $configuration['controllerClass']::${$configuration['defaultView']};

            endif;

            // update incoming url
            URL::setIncomingUri($incomingUrl);

        endif;

        // check if view method doesn't exists
        if (!$configuration['reflection']->hasMethod($view)) :

            // load page not found
            self::loadStarterPack('page-not-found'); 
            die();
            
        endif;

        // load guard
        self::loadViewGuard($view);

        // return incoming url 
        return URL::getIncomingUri();
    }
}   