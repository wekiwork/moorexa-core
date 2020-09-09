<?php
namespace Lightroom\Packager\Moorexa\MVC\Helpers;

use Exception;
use Lightroom\Core\{
    Payload, PayloadRunner
};
use Lightroom\Packager\Moorexa\Router;
use Lightroom\Router\RouterHandler;
use Lightroom\Adapter\ClassManager;
use Lightroom\Exceptions\ClassNotFound;
use ReflectionClass;
use ReflectionException;
use function Lightroom\Requests\Functions\session;
use Lightroom\Packager\Moorexa\Helpers\{UrlControls, URL};
/**
 * @package Moorexa ControllerLoader Handler
 * @author Amadi Ifeanyi <amadiify.com>
 */
class ControllerLoader
{
    use ControllerServeMethods;

    /**
     * @var ControllerLoader FIRST_PARAM
     */
    const FIRST_PARAM = 0;

    /**
     * @var ControllerLoader SECOND_PARAM
     */
    const SECOND_PARAM = 1;

    /**
     * @var ControllerLoader THIRD_PARAM
     */
    const THIRD_PARAM = 2;

    /**
     * @var string $starterTitle
     */
    private static $starterTitle = '';

    /**
     * @method ControllerLoader useDefaultRoutingMechanism
     * @return array
     *
     * This method tries load a route with the default routing mechanism
     * @throws Exception
     */
    public static function useDefaultRoutingMechanism() : array
    {
        // @var array $incomingUrl
        $incomingUrl = URL::getIncomingUri();

        // manage request internally
        if (count($incomingUrl) > self::FIRST_PARAM) :

            // @var int $position
            // where to begin
            $position = self::FIRST_PARAM;

            // manage request internally.
            if (is_array($incomingUrl) && count($incomingUrl) > self::SECOND_PARAM) :
            
                // shift cursor
                $position = self::SECOND_PARAM;

                // shift cursor
                if(isset($incomingUrl[(int) self::THIRD_PARAM]) || count($incomingUrl) >= self::THIRD_PARAM) $position = self::THIRD_PARAM;

            endif;

            // get route from position
            $returnArray = self::getRouteFromPosition((int) $position, $incomingUrl);

            // check default controller
            if ($returnArray['checkActive']) :
            
                // get default controller
                $defaultController = Router::readConfig('router.default.controller');

                // get controller path
                $controllerPath = self::getControllerPath($defaultController);

                // check if controller exist
                if (file_exists($controllerPath) && isset($incomingUrl[(int) self::FIRST_PARAM])) :
                
                    // get the view
                    $view = UrlControls::cleanUrl($incomingUrl[(int) self::FIRST_PARAM])[0];

                    // check if method exists
                    if (stristr(file_get_contents($controllerPath), 'function '.$view) !== false) :
                    
                        $returnArray['continue'] = false;

                        // push controller to the beginning of array
                        array_unshift($incomingUrl, $defaultController); 

                        // reformat incoming url
                        $incomingUrl = array_unique($incomingUrl);
                    
                    endif;

                endif;

            endif;

        else:

            // build incoming url with default controller and view
            $incomingUrl = [

                // default controller
                Router::readConfig('router.default.controller'),

                // default view
                Router::readConfig('router.default.view')
            ];

        endif;

        // return array
        return $incomingUrl;
    }

    /**
     * @method ControllerLoader config
     * @param string $target
     * @param mixed $targetFallback
     * @param mixed $controllerFallback
     * @return mixed
     *
     * This method reads the config.xml file from within a controller directory
     * @throws Exception
     */
    public static function config(string $target, $targetFallback = null, string $controllerFallback = '')
    {
        static $config;

        // @var mixed $returnValue
        $returnValue = $targetFallback;

        // @var string $controller
        $controller = $controllerFallback;

        // load active controller if $controller is an empty string
        if ($controller == '') :

            // get incoming url
            $incomingUrl = URL::getIncomingUri();

            // update controller
            if (count($incomingUrl) > 0) $controller = $incomingUrl[(int) self::FIRST_PARAM];

        endif;

        // update controller
        $controller = ucfirst($controller);

        // continue if $controller is not null

        if ($controller !== '') :

            // get configuration path 
            $configurationPath = self::basePath() . '/' . $controller . '/config.php';

            if (file_exists($configurationPath)) :
            
                /**
                 * @var bool $add 
                 * A flag that determines if $controller configuration should be cached.
                 */
                $add = false;

                // check if not cached previously
                if (is_null($config) || (is_array($config) && !isset($config[$controller]))) $add = true;

                // cache configuration
                if ($add)  $config[$controller] = include_once $configurationPath;

                // load configuration
                $configuration = $config[$controller];

                // try merge configuration 
                if (!isset($configuration['use.default'])) :

                    // get bootstrap config
                    $bootstrapConfig = env('bootstrap', 'controller_config');

                    // merge if it's an array
                    if (is_array($bootstrapConfig) && is_array($configuration)) $configuration = array_merge($configuration, $bootstrapConfig);

                endif;

                // check if target exists
                if (isset($configuration[$target])) :
                
                    // get target
                    $target = $configuration[$target];

                    if (is_array($target) && is_string($targetFallback) && strlen($targetFallback) > 1) :
                    
                        // update target
                        $target = isset($target[$targetFallback]) ? $target[$targetFallback] : null;
                    
                    endif;

                    // update return value
                    $returnValue = $target;

                endif;
            
            else:
            
                // get constant
                if ($target == 'directory') $returnValue = constant('CONTROLLER_'.strtoupper($targetFallback));

            endif;

        else:

            if (Router::$routeSatisfied === false) :
                // throw exception
                throw new Exception('Controller could not be found. Tried using fallback, but failed also.');
            endif;


        endif;

        // return mixed
        return $returnValue;
    }

    /**
     * @method ControllerLoader getControllerPath
     * @param string $controller
     * @return string
     * @throws Exception
     */
    public static function getControllerPath(string $controller) : string 
    {
        // update controller
        $controller = ucfirst($controller);

        // get path
        $path = self::config('main.entry', 'main.php', $controller);

        // return string
        return file_exists($path) ? $path : self::basePath() .'/'. $controller .'/' . $path;
    }

    /**
     * @method ControllerLoader serveController
     * @return mixed
     *
     * This method receives the incoming url, loads the route or trigger any of the starter templates.
     * @throws Exception
     */
    public static function serveController()
    {
        // @var array $incomingUrl
        $incomingUrl = URL::getIncomingUri();

        // get controller and view
        $controller = UrlControls::cleanUrl($incomingUrl[(int) self::FIRST_PARAM])[0];

        // update controller
        $incomingUrl[(int) self::FIRST_PARAM] = $controller;

        // register starter title
        self::$starterTitle = is_string(RouterHandler::getStarterPack('title')) ? RouterHandler::getStarterPack('title') : '';

        // get target
        $target = env('bootstrap', 'maintaince-mode') ? 'maintenance' : 'coming-soon';

        // check if coming soon or maintenance mode has been activated, load from starter pack
        if (env('bootstrap', 'maintaince-mode') || env('bootstrap', 'coming-soon')) return self::loadStarterPack($target);

        // load starter if requested
        if ($controller == self::$starterTitle) return self::loadStarterPack('main');

        // load controller base path checker
        if (file_exists(func()->const('extra') . '/controllers.php')) include_once func()->const('extra') . '/controllers.php';

        // Get controller class
        $controllerClass = self::getControllerClass($controller);

        // load controller and view
        if (is_string($controllerClass) && class_exists($controllerClass)) self::loadControllerAndView($controllerClass, $incomingUrl);

        return null;
    }

    /**
     * @method ControllerLoader getControllerClass
     * @param string $controller
     * @return mixed
     */
    public static function getControllerClass(string $controller) 
    {
        if ($controller != '') :

            // check if controller class exists
            $controllerPath = self::getControllerPath($controller);

            // load page not found 
            if (!file_exists($controllerPath)) return self::loadStarterPack('page-not-found');

            // include controller
            include_once $controllerPath;

            // build controller class from namespace
            $controllerClass = RouterHandler::getStarterPack('framework-namespace') . '\\' . self::getNamespacePrefix() . ucfirst($controller);

            // throw exception if class isn't found
            if (!class_exists($controllerClass)) return self::loadStarterPack('invalid-controller', $controllerClass);

            // return class
            return $controllerClass;

        endif;
    }

    /**
     * @method ControllerLoader getNamepacePrefix
     * @return string
     */
    public static function getNamespacePrefix() : string 
    {
        // @var string $returnValue
        $returnValue = '';

        // get controller namespace prefix
        $namespacePrefix = env('bootstrap', 'controller.namespace_prefix');

        // clean up prefix
        if (is_string($namespacePrefix) && strlen($namespacePrefix) > 1)  $returnValue = ucfirst(preg_replace('/[^a-z0-9A-Z\_]/', '', $namespacePrefix)) . '\\';

        // return string
        return $returnValue;
    }

    /**
     * @method ControllerLoader loadControllerWithView
     * @param string $controller
     * @param array $returnValue (reference)
     * @param array $incomingUrl (reference)
     * @return void
     * @throws Exception
     */
    private static function loadControllerWithView(string $controller, array &$returnValue, array &$incomingUrl) : void
    {
        // get path
        $controllerPath = self::getControllerPath($controller);

        if (file_exists($controllerPath))
        {
            // get default view
            $defaultView = Router::readConfig('router.default.view');

            // check function implementation
            if (stristr(file_get_contents($controllerPath), 'function '.$defaultView) !== false) :
            
                // update return value
                $returnValue['checkActive'] = false;
                $returnValue['continue'] = false;

                // update view
                $incomingUrl[1] = $defaultView;
                
            endif;
        }
    }

    /**
     * @method ControllerLoader getRouteFromPosition
     * @param int $position
     * @param array $incomingUrl (reference)
     * @return array
     *
     * This method loads a route from a url position
     * @throws Exception
     */
    private static function getRouteFromPosition(int $position, array &$incomingUrl) : array
    {
        // @var array $returnValue
        $returnValue = ['checkActive' => true, 'continue' => true];

        // now where do we begin checking
        switch ($position)
        { 
            // a controller and a view requested.
            case 2:

                // unpack incoming url
                list($controller, $view) = UrlControls::cleanUrl($incomingUrl);

                // get controller path
                $controllerPath = self::getControllerPath($controller);

                // the check
                if (file_exists($controllerPath)) :
                
                    // ok we have something.
                    // check for function implementation for view
                    if (stristr(file_get_contents($controllerPath), 'function '.$view) !== false) :
                    
                        // controller found
                        $returnValue['checkActive'] = false;
                        $returnValue['continue'] = false;

                    endif;

                endif;

                break;

            // start from the beginning. try auto find.
            case 0:

                // unpack incoming url
                list($controller) = UrlControls::cleanUrl($incomingUrl);

                // set continue as true
                $returnValue['continue'] = true;

                // check if session saved an active controller
                if (session()->has('__active__controller')) :
                
                    // @var string $activeController
                    $activeController = session()->get('__active__controller');

                    // compare strings and load controller with view
                    if (strtolower($activeController) == strtolower($controller)) self::loadControllerWithView($controller, $returnValue, $incomingUrl);

                endif;

                // will execute only if session check failed. load controller with view
                if ($returnValue['continue']) self::loadControllerWithView($controller, $returnValue, $incomingUrl);
                
                break;
        }

        return $returnValue;
    }

    /**
     * @method ControllerLoader setCurrentPath
     * @param string $controller
     * @param array $configuration
     * @return void
     */
    protected static function setCurrentPath(string $controller, array $configuration) : void
    {
        if ($controller != self::$starterTitle) :
         
            // get base path
            $controllerBasePath = self::basePath();

            // define path
            foreach ($configuration as $constant => $basePath) :

                // build sub directory
                $subDirectory = $controllerBasePath . '/' . $controller . '/' . $basePath . '/';

                // check if directory exists
                if (is_dir($subDirectory)) :
                
                    // is defined?
                    $constant = strtoupper(basename($controller)) . '_' . strtoupper($constant);

                    // remove multiple '/'
                    $subDirectory = preg_replace('/[\/]{2,}/', '/', $subDirectory);

                    // define
                    if (!defined($constant)) define($constant, $subDirectory);

                endif;

            endforeach;

        endif;
    }

    /**
     * @method ControllerLoader loadStarterPack
     * @param string $target
     * @param array $arguments
     * @return mixed
     */
    private static function loadStarterPack(string $target, ...$arguments)
    {
        // get starter
        $starter = RouterHandler::getStarterPack($target);

        // if it's an array, then use call_user_func method
        if (is_array($starter)) : 

            // call function
            call_user_func_array($starter, $arguments);

        elseif (is_string($starter) && file_exists($starter)) :

            // include starter file
            include_once $starter;

        endif;

        return null;
    }

    /**
     * @method ControllerLoader loadControllerAndView
     * @param string $controllerClass
     * @param array $incomingUrl
     * @return mixed
     * @throws ClassNotFound
     * @throws ReflectionException
     */
    private static function loadControllerAndView(string $controllerClass, array $incomingUrl) 
    {
        // @var ReflectionClass $reflection
        $reflection = new ReflectionClass($controllerClass);

        // @var string $defaultView
        $defaultView = Router::readConfig('router.default.view');

        // get the view
        $view = UrlControls::cleanUrl(isset($incomingUrl[(int) self::SECOND_PARAM]) ? $incomingUrl[(int) self::SECOND_PARAM] : $defaultView)[0];

        // check if view method doesn't exists
        if (!$reflection->hasMethod($view)) return self::loadStarterPack('page-not-found');

        // update incoming url with view
        $incomingUrl[(int) self::SECOND_PARAM] = $view;

        // update incoming url
        URL::setIncomingUri($incomingUrl);

        // load controller guard if found
        self::loadControllerGuard();
        
        // we load the default view guard and receive an updated incoming url
        $incomingUrl = self::loadDefaultViewAndGuard(
        [
            'defaultView' => $defaultView,
            'reflection' => &$reflection,
            'controllerClass' => $controllerClass
        ]);

        // try trigger controller event
        if (event()->canEmit('ev.controller.ready')) event()->emit('ev', 'controller.ready', $incomingUrl);

        // load controller payloads
        self::loadControllerPayloads($incomingUrl, $reflection);

        return null;
    }

    /**
     * @method ControllerLoader loadControllerPayloads
     * @param array $incomingUrl
     * @param ReflectionClass $reflection
     * @return void
     * @throws ClassNotFound
     */
    private static function loadControllerPayloads(array $incomingUrl, ReflectionClass $reflection) : void
    {
        // using payloads 
        $payload = ClassManager::singleton(Payload::class)->clearPayloads();

        // get payload runner
        $payloadRunner = ClassManager::singleton(PayloadRunner::class);

        /**
         * @package Payload ControllerMiddlewareLoader
         * 
         * This attaches a middleware switch for the incoming request
         */
        $payload->register('attach-middleware', $payload->handler(ControllerMiddlewareLoader::class)->arguments($incomingUrl));

        /**
         * @package Payload ControllerViewHandler
         * 
         * This attaches an handler for every view requests.
         */
        $payload->register('attach-view', $payload->handler(ControllerViewHandler::class)->arguments($incomingUrl, $reflection, function() use ($incomingUrl, $payload, $payloadRunner)
        {
            // using payloads 
            $payload->clearPayloads();
        
            /**
             * @package Payload Registry
             * 
             * This includes the payload registry for external load stack
             */
            $payloadRegistry = func()->const('services') . '/payloads.php';

            // include registry if file exists
            if (file_exists($payloadRegistry)) include_once $payloadRegistry;

            // load processes
            $payload->loadProcesses($payloadRunner);

        }));

        // load processes
        $payload->loadProcesses($payloadRunner);
    }
}