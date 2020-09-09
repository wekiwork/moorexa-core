<?php
namespace Lightroom\Adapter\Configuration\Interfaces;

/**
 * @package configuration socket handler interface
 * @author amadi ifeanyi <amadiify.com>
 * @author fregatelab <fregatelab.com>
 */
interface ConfigurationSocketHandlerInterface
{
    /**
     * @method ConfigurationSocketHandlerInterface setClass
     * @param string $className
     * @return ConfigurationSocketHandlerInterface
     */
    public function setClass(string $className) : ConfigurationSocketHandlerInterface;

    /**
     * @method ConfigurationSocketHandlerInterface setMethod
     * @param string $method
     * @return ConfigurationSocketHandlerInterface
     */
    public function setMethod(string $method) : ConfigurationSocketHandlerInterface;
    /**
     * @method ConfigurationSocketHandlerInterface getClass
     */
    public function getClass() : string;
    /**
     * @method ConfigurationSocketHandlerInterface getMethod
     */
    public function getMethod() : string;
}