<?php
namespace Lightroom\Templates\Functions;

use Lightroom\Adapter\ClassManager;
use Lightroom\Exceptions\ClassNotFound;
use Lightroom\Packager\Moorexa\MVC\{
    View, Controller
};
use Lightroom\Packager\Moorexa\Interfaces\{
    ControllerInterface
};
use Lightroom\Packager\Moorexa\Helpers\UrlControls;

/**
 * @method View Handler
 * @return View
 * @throws ClassNotFound
 */
function view() : View 
{
    return ClassManager::singleton(View::class);
}

/**
 * @method Controller controller
 * @return ControllerInterface
 */
function controller() : ControllerInterface
{
    return Controller::getInstance();   
}

/**
 * @method Controller viewVariables
 * @return array
 */
function viewVariables() : array 
{
    return Controller::getViewVariables();
}

/**
 * @method Controller request
 * @return mixed 
 */
function request()
{
    // @var request class 
    static $request;

    // create class
    if ($request === null) $request = UrlControls::getControllerViewAndArgs();

    // return class
    return $request;
}

/**
 * @method Controller setViewVariables
 * @param string $variable
 * @param mixed $value
 * @return void
 */
function setViewVariable(string $variable, $value) : void 
{
    Controller::getInstance()->{$variable} = $value;
}