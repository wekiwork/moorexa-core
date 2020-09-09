<?php
namespace Lightroom\Templates\Functions;

use Lightroom\Templates\Interfaces\TemplateHandlerInterface;
use Lightroom\Templates\TemplateHandler;
/**
 * @method TemplateHandlerInterface render
 * @param mixed $path
 * @param mixed ...$arguments
 * @return void
 */
function render($path, ...$arguments) : void 
{
    // push path to index zero
    array_unshift($arguments, $path);

    // call render method
    call_user_func_array([TemplateHandler::class, 'render'], $arguments);
}

/**
 * @method TemplateHandlerInterface redirect
 * @param string $path
 * @param array $arguments
 * @return mixed
 */
function redirect(string $path = '', array $arguments = [])  
{
    // call render method
    return call_user_func_array([TemplateHandler::class, 'redirect'], [$path, $arguments]);
}