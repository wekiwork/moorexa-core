<?php
namespace Lightroom\Core\Interfaces;

use closure;

/**
 * @package Function wrapper interface
 */
interface FunctionWrapperInterface
{
    /**
     * @method FunctionWrapperInterface create
     * create a function. take a name and closure function,
     * this function can be attached to a class
     * @param string $functionName
     * @param closure $functionClosure
     * @return FunctionWrapperInterface
     */
    public function create(string $functionName, closure $functionClosure) : FunctionWrapperInterface;

    /**
     * @method FunctionWrapperInterface attachTo
     * attach a wrapped function to a listening class
     * @param string $className
     * @return FunctionWrapperInterface
     */
    public function attachTo(string $className) : FunctionWrapperInterface;

    /**
     * @method FunctionWrapperInterface global functions from file
     * @param string $filepath
     */
    public function loadGlobalFunction(string $filepath);
}