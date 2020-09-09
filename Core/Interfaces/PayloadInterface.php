<?php
namespace Lightroom\Core\Interfaces;

interface PayloadInterface 
{
    /**
     * @method PayloadInterface setClass
     * set fetch class
     * @param string $classname
     */
    public function setClass(string $classname);

    /**
     * @method PayloadInterface setMethod
     * set fetch class method
     * @param string $method
     */
    public function setMethod(string $method);

    /**
     * @method PayloadInterface getClass
     * get fetch class
     */
    public function getClass() : string;

    /**
     * @method PayloadInterface getMethod
     * get fetch class method
     */
    public function getMethod() : string;

    /**
     * @method PayloadInterface arguments
     * arguments for class methods
     */
    public function arguments();

    /**
     * @method PayloadInterface getArguments
     * getArguments for class methods
     */
    public function getArguments() : array;
}