<?php
namespace Lightroom\Adapter\Configuration\Interfaces;

/**
 * @package Environment interface 
 * @author amadi ifeanyi <amadiify.com>
 */
interface EnvironmentInterface
{
    /**
     * @method EnvironmentInterface setEnv
     * Set a key, value to environment
     * @param string $key
     * @param mixed $value
     */
    public static function setEnv(string $key, $value);

    /**
     * @method EnvironmentInterface getEnv
     * return value from environment vars for a key.
     * @param string $key
     * @return mixed
     */
    public static function getEnv(string $key);
}