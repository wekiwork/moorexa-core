<?php
namespace Lightroom\Packager\Moorexa\MVC\Helpers;

use ReflectionClass;
use Lightroom\Adapter\ClassManager;
use Lightroom\Router\RouterHandler;
use Lightroom\Exceptions\ClassNotFound;
use Lightroom\Packager\Moorexa\Interfaces\{
    ControllerInterface, ViewProviderInterface, 
    ControllerProviderInterface
};
use ReflectionException;

/**
 * @package Controller View Provider
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait ControllerViewProvider
{
    /**
     * @var bool $hasControllerProvider
     */
    private static $hasControllerProvider = false;

    /**
     * @var bool $providerLoaded
     */
    private static $providerLoaded = false;

    /**
     * @method ControllerViewHandler loadViewParametersProvider
     * @return void
     * @throws ReflectionException
     */
    private function loadViewParametersProvider() : void 
    {
        // get view parameters
        foreach ($this->viewParameters as $parameter) :

            // continue with object
            if (is_object($parameter)) :

                // create reflection class
                $reflection = new ReflectionClass($parameter);

                // check if class implements ViewProviderInterface
                if ($reflection->implementsInterface(ViewProviderInterface::class)) :

                    // update view can load
                    $this->viewCanLoad = false;

                    // set view arguments
                    $parameter->setArguments($this->getArguments());

                    // load view provider
                    $this->loadViewProvider($parameter);

                endif;

                // check if class implements ViewProviderInterface
                if ($reflection->implementsInterface(ControllerProviderInterface::class)) :

                    // update view can load
                    $this->viewCanLoad = false;

                    // set view arguments
                    $parameter->setArguments($this->getArguments());

                    // load controller provider
                    $this->loadControllerProvider($parameter);

                endif;

            endif;

        endforeach;
    }

    /**
     * @method ControllerViewHandler loadControllerProviderFromFile
     * @return void
     * @throws ClassNotFound
     */
    private function loadControllerProviderFromFile() : void 
    {
        // @var string $namespace
        $namespace = RouterHandler::getStarterPack('framework-namespace');

        if ($namespace !== null) :

            // @var string $getPath
            $getPath = self::config('default.provider', 'Provider.php', $this->getController());

            // @var string $provider path
            $providerPath = parent::basePath() .'/'. 
                            $this->getController() .'/' . 
                            self::config('default.provider', 'Provider.php', $this->getController());

            // OVERWRITE if possible
            $providerPath = file_exists($getPath) ? $getPath : $providerPath;

            // continue if path exists
            if (file_exists($providerPath)) :

                // include file
                include_once ($providerPath);

                // add provider class name to namespace
                $providerClass = $namespace . '\\' . self::getNamespacePrefix() . $this->getController() . '\Provider';

                // continue if provider class exists
                if (!class_exists($providerClass)) throw new ClassNotFound($providerClass);

                // update view can load
                $this->viewCanLoad = false;

                // has controller provider
                self::$hasControllerProvider = true;

                // load controller provider
                $this->loadControllerProvider(ClassManager::singleton($providerClass));

            endif;

        endif;
    }

    /**
     * @method ControllerViewHandler loadViewProvider
     * @param ViewProviderInterface $provider
     * @param array $arguments
     * @return void
     */
    private function loadViewProvider(ViewProviderInterface $provider, array $arguments = []) : void 
    {
        // create closure function
        $closure = function() use (&$provider, $arguments)
        { 
            // load view action
            $arguments = count($arguments) == 0 ? $this->getArguments() : $arguments;

            // @var string $action
            $action = 'show';

            // function to format method
            $formatMethod = function(string $method) : string
            {
                // clean method
                $method = preg_replace('/[^a-zA-Z0-9\_]/', ' ', $method);

                // convert method to array
                $methodArray = explode(' ', $method);

                // run through the arrays
                if (count($methodArray) > 1) :

                    foreach ($methodArray as $index => $methodInIndex) :

                        if ($index > 0) :

                            // update string
                            $methodArray[$index] = ucfirst(trim($methodInIndex));

                        endif;

                    endforeach;

                    // update method
                    $method = trim(implode('', $methodArray));

                endif;

                // return string
                return $method;
            };

            // check if action has been set
            if (isset($arguments[0]) && is_string($arguments[0])) :

                // update action
                $action2 = $arguments[0];

                // clean action2
                $action2 = $formatMethod($action2);

                // check if method exists
                if (method_exists($provider, $action2)) :

                    // update action
                    $action = $action2;

                    // update arguments
                    $arguments = array_splice($arguments, 1);

                endif;

            endif;

            // clean action
            $action = $formatMethod($action);

            // view can load
            if (!method_exists($provider, $action)) $this->viewCanLoad = true;

            // load action
            if (method_exists($provider, $action)) :

                // set the controller
                $provider->controller = $this->controller;

                // set the view
                $provider->view = $this->controller->view;

                // @var array $parameters
                $parameters = $this->getProviderParameters($provider, $action, $arguments);

                // loading a provider within provider
                if (count($parameters) > 0 && self::$providerLoaded === false) :

                    // check for provider
                    foreach ($parameters as $parameter) :

                        // object 
                        if (is_object($parameter)) :

                            // create reflection class
                            $reflection = new \ReflectionClass($parameter);

                            if ($reflection->implementsInterface(ViewProviderInterface::class)) :

                                $this->loadViewProvider($parameter, $arguments);

                            endif;
                            
                        endif;

                    endforeach;

                endif;

                if (self::$providerLoaded === false) :

                    // trigger view.action.ready
                    if (event()->canEmit('ev.view.action.ready')) event()->emit('ev', 'view.action.ready', [
                        'action' => &$action,
                        'parameters' => &$parameters,
                        'provider' => &$provider
                    ]);

                    // load model
                    $this->loadViewModelFromParameter($parameters);

                    // set the model
                    if ($this->controller->hasModel()) $provider->model = $this->controller->model;

                    // load view witll enter
                    $this->loadViewWillEnterForControllerProvider($action, $parameters);

                    // call action method
                    call_user_func_array([$provider, $action], $parameters);

                    // provider loaded
                    self::$providerLoaded = true;

                endif;

            endif;
        
        };

        // trigger view provider event
        if (event()->canEmit('ev.view.provider.ready')) event()->emit('ev', 'view.provider.ready', [
            'controller' => &$this->controller, 
            'provider' => &$provider
        ]);

        // pass current view
        $provider->currentView = $this->getView();

        // set active view provider
        $this->controller->setActiveViewProvider($provider);

        // load view will enter method
        $provider->viewWillEnter($closure->bindTo($this, static::class));
    }

    /**
     * @method ControllerViewHandler loadControllerProvider
     * @param ControllerProviderInterface $provider
     * @return void
     */
    private function loadControllerProvider(ControllerProviderInterface $provider) : void 
    {
        // create closure function
        $closure = function(){ $this->viewCanLoad = true; };

        // trigger controller provider event
        if (event()->canEmit('ev.controller.provider.ready')) event()->emit('ev', 'controller.provider.ready', [
            'controller' => &$this->controller, 
            'provider' => &$provider
        ]);

        // pass current view
        $provider->currentView = $this->getView();

        // pass controller
        $provider->controller = $this->controller;

        // set active controller provider
        $this->controller->setActiveControllerProvider($provider);

        // load boot method
        $provider->boot($closure->bindTo($this, static::class));
    }

     /**
     * @method ControllerViewProvider getViewParameters
     * @param ViewProviderInterface $provider
     * @param string $method
     * @param array $arguments
     * @return array
     * @throws ReflectionException
     */
    private function getProviderParameters(ViewProviderInterface $provider, string $method, array $arguments) : array 
    {
        // @var array $parameters
        $parameters = [];

        // reflection
        $reflection = new \ReflectionClass(get_class($provider));
        
        // load method
        $reflectionMethod = $reflection->getMethod($method);

        // continue if number of parameters if greater than zero
        if ($reflectionMethod->getNumberOfParameters() > 0) :

            // get parameters using class manager
            ClassManager::getParameters($provider, $method, $parameters, $arguments);

        endif;

        // return array
        return $parameters;
    }

    /**
     * @method ControllerViewProvider loadViewWillEnterForControllerProvider
     * @param string $view
     * @param array $parameters
     * @return void
     */
    private function loadViewWillEnterForControllerProvider(string $view, array &$parameters) : void
    {
        // load view will enter from provider
        if (self::$hasControllerProvider) :

            // get controller provider
            $provider = $this->controller->getActiveControllerProvider();

            // call view will enter method
            $provider->viewWillEnter($view, $parameters);

        endif;
    }
}