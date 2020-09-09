<?php
namespace Lightroom\Common\Interfaces;

use Lightroom\Common\Interfaces\ExceptionWrapperInterface;

interface ExceptionHandlerInterface
{
    /**
     * @method ExceptionHandlerInterface exception class name
     * @param string $className
     * @return ExceptionHandlerInterface
     */
    public function exceptionClass(string $className) : ExceptionHandlerInterface;

    /**
     * @method ExceptionHandlerInterface exception class method
     * @param string $method
     * @return ExceptionHandlerInterface
     */
    public function exceptionMethod(string $method) : ExceptionHandlerInterface;
    /**
     * @method ExceptionHandlerInterface get exception class name
     */
    public function getExceptionClass() : string;
    /**
     * @method ExceptionHandlerInterface get exception class method
     */
    public function getExceptionMethod() : string;

    /**
     * @method ExceptionHandlerInterface saveHandlerInstance
     * @param \Lightroom\Common\Interfaces\ExceptionWrapperInterface $handler
     */
    public function saveHandlerInstance(ExceptionWrapperInterface $handler) : void;
    /**
     * @method ExceptionHandlerInterface getHandlerInstance
     */
    public function getHandlerInstance() : ExceptionWrapperInterface;
}