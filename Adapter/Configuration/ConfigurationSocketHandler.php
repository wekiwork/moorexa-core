<?php
namespace Lightroom\Adapter\Configuration;

use Lightroom\Core\Setters\Constants;
use Lightroom\Adapter\Configuration\Interfaces\ConfigurationSocketHandlerInterface;
/**
 * @package configuration socket handler
 * @author amadi ifeanyi <amadiify.com>
 * @author fregatelab <fregatelab.com>
 */
class ConfigurationSocketHandler extends Constants implements ConfigurationSocketHandlerInterface 
{
    /**
     * @method ConfigurationSocketHandlerInterface setClass
     * @param string $className
     * @return ConfigurationSocketHandlerInterface
     */
    public function setClass(string $className) : ConfigurationSocketHandlerInterface
    {
        // using constant setter
        $this->name($className);

        // return instance
        return $this;
    }

    /**
     * @method ConfigurationSocketHandlerInterface setMethod
     * @param string $method
     * @return ConfigurationSocketHandlerInterface
     */
    public function setMethod(string $method) : ConfigurationSocketHandlerInterface
    {
        $this->value($method);

        // return instance
        return $this;
    }

    /**
     * @method ConfigurationSocketHandlerInterface getClass
     */
    public function getClass() : string
    {
        return $this->getName();
    }

    /**
     * @method ConfigurationSocketHandlerInterface getMethod
     */
    public function getMethod() : string
    {
        return $this->getValue();
    }
}