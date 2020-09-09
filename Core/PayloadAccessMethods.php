<?php
namespace Lightroom\Core;

use Lightroom\Core\Interfaces\PayloadInterface;

/**
 * @package Payload Access Methods
 * @author fregatelab <fregatelab.com>
 * @author Amadi Ifeanyi <amadiify.com>
 */

class PayloadAccessMethods implements PayloadInterface
{
    /**
     * @var string $className
     */
    private $className;

    /**
     * @var string $classMethod
     */
    private $classMethod;

    /**
     * @var array $classArguments
     */
    private $classArguments = [];


    /**
     * @method PayloadAccessMethods setClass
     * set fetch class
     * @param string $classname
     */
    public function setClass(string $classname)
    {
        $this->className = $classname;
    }

    /**
     * @method PayloadAccessMethods setMethod
     * set fetch class method
     * @param string $method
     */
    public function setMethod(string $method)
    {
        $this->classMethod = $method;
    }

    /**
     * @method PayloadAccessMethods getClass
     * get fetch class
     */
    public function getClass() : string
    {
        return $this->className;
    }

    /**
     * @method PayloadAccessMethods getMethod
     * get fetch class method
     */
    public function getMethod() : string
    {
        return $this->classMethod;
    }

    /**
     * @method PayloadAccessMethods arguments
     * @param mixed $arguments
     * set class or method arguments
     * @return PayloadAccessMethods
     */
    public function arguments(...$arguments)
    {
        // get function arguments
        $this->classArguments = $arguments;

        // return instance
        return $this;
    }

    /**
     * @method PayloadAccessMethods getArguments
     * get class Arguments
     */
    public function getArguments() : array
    {
        return $this->classArguments;
    }
}