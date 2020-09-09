<?php
namespace Lightroom\Adapter\Configuration\Interfaces;

use Lightroom\Adapter\Configuration\Interfaces\SetSocketArrayInterface;

/**
 * @package Configuration socket interface
 */
interface ConfigurationSocketInterface
{
    /**
     * @method ConfigurationSocketInterface configurationSocket
     * Apply a configuration socket for environment methods
     * @param array $socketArray
     */
    public function configurationSocket(array $socketArray);

    /**
     * @method ConfigurationSocketInterface getSocketArray
     */
    public function getSocketArray() : array;

    /**
     * @method ConfigurationSocketInterface getSocketArray
     * @param string $method
     * @param \Lightroom\Adapter\Configuration\Interfaces\SetSocketArrayInterface $socketArray
     */
    public function setSocketArray(string $method, SetSocketArrayInterface $socketArray);

    /**
     * @method ConfigurationSocketInterface callMethodFromConfigurationSocket
     * @param string $runtimeMethod
     * @param array $arguments
     */
    public function callMethodFromConfigurationSocket(string $runtimeMethod, array $arguments);
}