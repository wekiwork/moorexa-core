<?php
namespace Lightroom\Packager\Moorexa\MVC\Helpers;

use Exception;
use ReflectionClass;
use Lightroom\Exceptions\{
    ClassNotFound, InterfaceNotFound
};
use Lightroom\Adapter\ClassManager;
use Lightroom\Router\RouterHandler;
use Lightroom\Packager\Moorexa\Interfaces\ModelInterface;
use function Lightroom\Functions\GlobalVariables\{var_get};
use ReflectionException;

/**
 * @package Controller View Model
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait ControllerViewModel
{
    /**
     * @var array $loadedModels
     */
    private $loadedModels = [];

    /**
     * @method ControllerViewHandler loadViewModelFromParameter
     * @param array $parameters 
     * @return void
     * @throws ReflectionException
     */
    private function loadViewModelFromParameter(array $parameters = []) : void 
    {
        // @var bool $loadDefault
        $loadDefault = true;

        // update parameters
        $parameters = count($parameters) == 0 ? $this->viewParameters : $parameters;

        // get loaded models
        $loadedModels = array_flip($this->loadedModels);

        // get view parameters
        foreach ($parameters as &$parameter) :

            // continue with object
            if (is_object($parameter)) :

                // get class name
                $className = get_class($parameter);

                // continue if not loaded previously
                if (!isset($loadedModels[$className])) :

                    // create reflection class
                    $reflection = new ReflectionClass($parameter);

                    // check if class implements ModelInterface
                    if ($reflection->implementsInterface(ModelInterface::class)) :

                        // push to loaded models to avoid calling this same model somewhere else
                        $this->loadedModels[] = $className;

                        // load view model
                        $parameter = $this->loadViewModel($parameter);

                        // do not load default
                        $loadDefault = false;

                    endif;

                endif;

            endif;

        endforeach;

        // try load default
        if ($loadDefault == true) $this->loadDefaultModel();
    }

    /**
     * @method ControllerViewHandler loadViewModel
     * @param ModelInterface $model
     * @return ModelInterface
     */
    private function loadViewModel(ModelInterface $model) : ModelInterface 
    {
        // load controller
        $model->controller = $this->controller;

        // trigger model.load event
        if (event()->canEmit('ev.model.load')) event()->emit('ev', 'model.load', [
            'controller' => &$model->controller,
            'model' => &$model
        ]);
        
        // load request method for view
        $model->loadRequestMethodForView($this, $model);

        // set active view model
        $this->controller->setActiveViewModel($model);

        // return ModelInterface
        return $model;
    }

    /**
     * @method ControllerViewModel loadDefaultModel
     * @return void
     * @throws ClassNotFound
     */
    private function loadDefaultModel() : void
    {
        // get model
        $model = parent::config('default.model');

        // default model exists in var_get ?
        $default = var_get('default.model');

        // set to model
        $model = is_string($default) && strlen($default) > 2 ? $default : $model;

        // check for model class
        if ($model != null && is_string($model)) :

            // model not found
            if (!class_exists($model)) throw new ClassNotFound($model);

            // create reflection class
            $reflection = new ReflectionClass($model);

            // check if class implements ModelInterface
            if ($reflection->implementsInterface(ModelInterface::class)) :

                // push to loaded model
                $this->loadedModels[] = $model;

                // load view model
                $this->loadViewModel($reflection->newInstanceWithoutConstructor());

            endif;

        endif;
    }

    /**
     * @method ControllerViewHandler loadViewModelFromFile
     * @return void
     * @throws InterfaceNotFound
     * @throws ReflectionException
     * @throws Exception
     */
    private function loadViewModelFromFile() : void 
    {
        // @var string $namespace
        $namespace = RouterHandler::getStarterPack('framework-namespace');

        if ($namespace !== null) :

            // @var array $loadedModels
            $loadedModels = array_flip($this->loadedModels);

            // add model class to namespace
            $modelClass = $namespace . '\\' . self::getNamespacePrefix() . $this->getController() . '\\' . 
                        parent::config('directory', 'model', $this->getController()) . '\\' .
                        ucfirst($this->getView());

            // continue if not loaded
            if (!isset($loadedModels[$modelClass])) :

                // check if model class does exists
                if (class_exists($modelClass)) :

                    // load reflection class
                    $reflection = new ReflectionClass($modelClass);

                    // ensure model implements ModelInterface
                    if (!$reflection->implementsInterface(ModelInterface::class)) throw new InterfaceNotFound($modelClass, ModelInterface::class);

                    // get model instance without constructor
                    $instance = $reflection->newInstanceWithoutConstructor();

                    // push to loaded models to avoid calling this same model somewhere else
                    $this->loadedModels[] = $modelClass;

                    // load view model
                    $this->loadViewModel($instance);

                endif;

            endif;

        endif;
    }

    /**
     * @method ControllerViewModel loadModelHttpFile
     * @return void
     */
    private function loadModelHttpFile() : void 
    {
        // @var string $path
        $path = parent::basePath() .'/'. $this->getController() .'/model.php';

        // check if file exists and include once
        if (file_exists($path)) include_once $path;
    }
}