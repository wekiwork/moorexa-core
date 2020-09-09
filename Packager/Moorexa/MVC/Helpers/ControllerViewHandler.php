<?php
namespace Lightroom\Packager\Moorexa\MVC\Helpers;

use Closure;
use Exception;
use Lightroom\Exceptions\ClassNotFound;
use Lightroom\Exceptions\InterfaceNotFound;
use Lightroom\Packager\Moorexa\Interfaces\ControllerInterface;
use ReflectionClass;
use Lightroom\Adapter\ClassManager;
use Lightroom\Router\RouterHandler;
use Lightroom\Core\FrameworkAutoloader;
use ReflectionException;

/**
 * @package ControllerViewHandler
 * @author Amadi Ifeanyi <amadiify.com>
 */
class ControllerViewHandler extends ControllerLoader
{
    use ControllerViewModel, ControllerViewProvider;

    /**
     * @var array $incomingUrl
     */
    private $incomingUrl = [];

    /**
     * @var ReflectionClass $reflection
     */
    private $reflection;

    /**
     * @var ControllerInterface $controller
     */
    private $controller;

    /**
     * @var array $viewParameters
     */
    private $viewParameters = [];

    /**
     * @var bool $viewCanLoad
     */
    private $viewCanLoad = true;

    /**
     * @method ControllerViewHandler __construct
     * @param  array $incomingUrl
     * @param  ReflectionClass $reflection
     * @param  Closure $callback
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public function __construct(array $incomingUrl, ReflectionClass $reflection, Closure $callback)
    {
        // prepare view
        $this->prepareView($incomingUrl, $reflection, $callback);
    }

    /**
     * @method ControllerViewHandler prepareView
     * @param  array $incomingUrl
     * @param  ReflectionClass $reflection
     * @param  Closure $callback
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public function prepareView(array $incomingUrl, ReflectionClass $reflection, Closure $callback)
    {
        // make incoming url available globally
        $this->incomingUrl = $incomingUrl;

        // build constants for controller
        $this->definePathsForSubDirectories();

        // get controller instance  
        $this->controller = $reflection->newInstanceWithoutConstructor();

        // make reflection available globally
        $this->reflection = $reflection;

        // make view parameters available globally
        $this->viewParameters = $this->getViewParameters();

        // init controller
        $this->controller->initController($this->controller);

        // load controller provider from file
        $this->loadControllerProviderFromFile();

        // load view model if allowed
        if ($this->viewCanLoad) : 

            // load model http file
            $this->loadModelHttpFile();

            // load closure and bind this handler
            call_user_func($callback->bindTo($this, static::class));
            
            // load view model from parameter
            $this->loadViewModelFromParameter();

            // load view model from file
            $this->loadViewModelFromFile();

            // load view load event
            if (event()->canEmit('ev.view.load')) event()->emit('ev', 'view.load', [
                'controller' => &$this->controller,
                'view' => $this->getView(),
                'params' => &$this->viewParameters
            ]);

            // load view parameter providers
            $this->loadViewParametersProvider();

            // load view
            if ($this->viewCanLoad) return $this->loadView();

        endif;
    }

    /**
     * @method ControllerViewHandler getArguments
     * @return array
     */
    public function getIncomingUrl() : array 
    {
        return $this->incomingUrl;
    }

    /**
     * @method ControllerViewHandler getViewParameters
     * @return array
     * @throws ReflectionException
     */
    public function getViewParameters() : array 
    {
        // @var array $parameters
        $parameters = [];
        
        // load method
        $method = $this->reflection->getMethod($this->getView());

        // continue if number of parameters if greater than zero
        if ($method->getNumberOfParameters() > 0) :

            // get parameters using class manager
            ClassManager::getParameters($this->controller, $this->getView(), $parameters, $this->getArguments());

        endif;

        // return array
        return $parameters;
    }

    /**
     * @method ControllerViewHandler getController
     * @return string
     * 
     * This method returns a controller name
     */
    public function getController() : string 
    {
        return ucfirst($this->incomingUrl[(int) parent::FIRST_PARAM]);
    }

    /**
     * @method ControllerViewHandler getView
     * @return string
     * 
     * This method returns a view name
     */
    public function getView() : string 
    {
        return $this->incomingUrl[(int) parent::SECOND_PARAM];
    }

    /**
     * @method ControllerViewHandler getArguments
     * @return array
     * 
     * This method returns all paths from index 2
     */
    public function getArguments() : array 
    {
        // @var array $incomingUrl
        $incomingUrl = $this->incomingUrl;

        // extract from the third parameter
        return array_splice($incomingUrl, (int) parent::THIRD_PARAM);
    }

    /**
     * @method ControllerViewHandler definePathsForSubDirectories
     * @return void
     *
     * This method creates constants for all the sub folders within a controller.
     * @throws Exception
     */
    private function definePathsForSubDirectories() : void
    {
        parent::setCurrentPath($this->getController(), [
            'path'      =>  '/',
            'custom'    =>  '/' . parent::config('directory', 'custom'),
            'model'     =>  '/' . parent::config('directory', 'model'),
            'package'   =>  '/' . parent::config('directory', 'package'),
            'provider'  =>  '/' . parent::config('directory', 'provider'),
            'static'    =>  '/' . parent::config('directory', 'static'),
            'partial'   =>  '/' . parent::config('directory', 'partial'),
            'view'      =>  '/' . parent::config('directory', 'view')
        ]);
    }

    /**
     * @method ControllerViewHandler loadView
     * @return mixed
     */
    private function loadView()
    {
        // load view witll enter
        $this->loadViewWillEnterForControllerProvider($this->getView(), $this->viewParameters);

        // set the response code
        http_response_code(200);

        // load views
        $view = call_user_func_array([$this->controller, $this->getView()], $this->viewParameters);

        // create view class for test environment
        if (defined('TEST_ENVIRONMENT_ENABLED')) :

            // clone instance
            $self =& $this->controller;

            // return a class for this view
            return new class($self, $view)
            {
                // @var mixed $model 
                public $model;

                // @var mixed $provider 
                public $provider;

                // @var mixed view
                public $view;

                // load properties
                public function __construct($controller, $view)
                {
                    $this->view = $view;
                    $this->provider = $controller->provider;
                    
                    try
                    {
                        $this->model = $controller->model;
                    }
                    catch(\Throwable $e){}
                }
            };

        endif;
    }
} 