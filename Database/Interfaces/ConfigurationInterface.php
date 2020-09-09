<?php
namespace Lightroom\Database\Interfaces;

/**
 * @package Configuration Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface ConfigurationInterface
{
    /**
     * @method ConfigurationInterface setHost
     * @param string $host
     * @return void
     */
    public function setHost(string $host) : void;

    /**
     * @method ConfigurationInterface setUser
     * @param string $user
     * @return void
     */
    public function setUser(string $user) : void;

    /**
     * @method ConfigurationInterface setName
     * @param string $name
     * @return void
     */
    public function setName(string $name) : void;

    /**
     * @method ConfigurationInterface setPass
     * @param string $pass
     * @return void
     */
    public function setPass(string $pass) : void;

    /**
     * @method ConfigurationInterface setDriver
     * @param string $driver
     * @return void
     */
    public function setDriver(string $driver) : void;

    /**
     * @method ConfigurationInterface setOther
     * @param array $other
     * @return void
     */
    public function setOther(array $other) : void;

    /**
     * @method ConfigurationInterface getHost
     * @return string
     */
    public function getHost() : string;

    /**
     * @method ConfigurationInterface getUser
     * @return string
     */
    public function getUser() : string;

    /**
     * @method ConfigurationInterface getName
     * @return string
     */
    public function getName() : string;

    /**
     * @method ConfigurationInterface getPass
     * @return string
     */
    public function getPass() : string;

    /**
     * @method ConfigurationInterface getDriver
     * @return string
     */
    public function getDriver() : string;

    /**
     * @method ConfigurationInterface getOther
     * @param string $title
     * @return string
     */
    public function getOther(string $title) : string;
}