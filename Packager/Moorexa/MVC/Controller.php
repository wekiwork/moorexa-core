<?php
namespace Lightroom\Packager\Moorexa\MVC;

use Lightroom\Packager\Moorexa\Interfaces\{
    ControllerInterface, ViewProviderInterface, 
    ControllerProviderInterface, ModelInterface
};
use Lightroom\Packager\Moorexa\MVC\Helpers\ControllerGuards;
use ReflectionException;

/**
 * @package Moorexa Controller
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * The default controller handler for our MVC
 */
class Controller implements ControllerInterface
{
    use Helpers\ControllerTriggers;

    /**
     * @var Controller $instance
     */
    private static $instance;

    /**
     * @var array $controllerSystemVariables
     */
    private static $controllerSystemVariables = [];

    /**
     * @var array $controllerViewVariables
     */
    private static $controllerViewVariables = [];

    /**
     * @var array $controllerSystemVariablesTriggers
     */
    private static $controllerSystemVariablesTriggers = [
        'view' => '___getView',
        'model' => '___getModel',
        'provider' => '___getProvider',
        'defaultProvider' => '___getDefaultProvider'
    ];

    /**
     * @method ControllerInterface initController
     * @param ControllerInterface $controller
     * @return void
     */
    public function initController(ControllerInterface $controller) : void
    {
        // set controller instance
        self::$instance = $controller;

        // include functions
        include_once __DIR__ . '/Helpers/Functions.php'; 
    }

    /**
     * @method ControllerInterface setActiveViewProvider
     * @param ViewProviderInterface $provider
     * @return void
     */
    public function setActiveViewProvider(ViewProviderInterface $provider) : void
    {
        self::$controllerSystemVariables['viewProvider'] = $provider;
    }

    /**
     * @method ControllerInterface getActiveViewProvider
     * @return ViewProviderInterface
     */
    public function getActiveViewProvider() : ViewProviderInterface
    {
        return self::$controllerSystemVariables['viewProvider'];
    }

    /**
     * @method ControllerInterface setActiveControllerProvider
     * @param ControllerProviderInterface $provider
     * @return void
     */
    public function setActiveControllerProvider(ControllerProviderInterface $provider) : void
    {
        self::$controllerSystemVariables['controllerProvider'] = $provider;
    }

    /**
     * @method ControllerInterface getActiveControllerProvider
     * @return ControllerProviderInterface
     */
    public function getActiveControllerProvider() : ControllerProviderInterface
    {
        return self::$controllerSystemVariables['controllerProvider'];
    }

    /**
     * @method ControllerInterface setActiveViewModel
     * @param ModelInterface $model
     * @return void
     */
    public function setActiveViewModel(ModelInterface $model) : void
    {
        self::$controllerSystemVariables['viewModel'] = $model;
    }

    /**
     * @method ControllerInterface hasModel
     * @return bool
     */
    public function hasModel() : bool 
    {
        return isset(self::$controllerSystemVariables['viewModel']) ? true : false;
    }

    /**
     * @method ControllerInterface loadControllerVariables
     * @return void
     * @throws ReflectionException
     */
    public function loadControllerVariables() : void
    {
        // create reflection class
        $reflection = new \ReflectionClass(self::getInstance());

        // get class properties
        $properties = $reflection->getProperties();

        // get properties
        foreach ($properties as $property) :

            if ($property->isPublic()) :

                // variable
                $variable = $property->getName();

                // add up
                self::$controllerViewVariables[$variable] = self::getInstance()->{$variable};

            endif;

        endforeach;
    }

    /**
     * @method Controller getInstance
     * @return ControllerInterface
     */
    public static function getInstance() : ControllerInterface
    {
        return self::$instance;
    }

    /**
     * @method Controller __get
     * @param string $variable
     * @return mixed
     */
    public function __get(string $variable)
    {
        // load from controller triggers
        $controllerTriggers = self::$controllerSystemVariablesTriggers;

        // check if variable exists in trigger and load from trigger.
        if (isset($controllerTriggers[$variable])) return call_user_func([$this, $controllerTriggers[$variable]]);

        // load from view variables
        $viewVariables = self::$controllerViewVariables;

        // check view variables
        if (isset($viewVariables[$variable])) return $viewVariables[$variable];

        return null;
    }

    /**
     * @method Controller __set
     * @param string $variable
     * @param mixed $value
     * @return void
     */
    public function __set(string $variable, $value) : void 
    {
        self::$controllerViewVariables[$variable] = $value;
    }

    /**
     * @method Controller setViewVars
     * @param string $variable
     * @param mixed $value
     * @return void
     */
    public static function setViewVars(string $variable, $value) : void 
    {
        self::$controllerViewVariables[$variable] = $value;
    }

    /**
     * @method Controller getViewVariables
     * @return array
     */
    public static function getViewVariables() : array 
    {
        return self::$controllerViewVariables;
    }
}