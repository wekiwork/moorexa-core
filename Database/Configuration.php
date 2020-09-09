<?php
namespace Lightroom\Database;

use Lightroom\Database\Interfaces\ConfigurationInterface;

/**
 * @package Configuration handler
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var array $configuration
     */
    private $configuration = [];

    /**
     * @method ConfigurationInterface setHost
     * @param string $host
     * @return void
     */
    public function setHost(string $host) : void
    {
        $this->configuration['host'] = $host;
    }

    /**
     * @method ConfigurationInterface setUser
     * @param string $user
     * @return void
     */
    public function setUser(string $user) : void
    {
        $this->configuration['user'] = $user;
    }

    /**
     * @method ConfigurationInterface setName
     * @param string $name
     * @return void
     */
    public function setName(string $name) : void
    {
        $this->configuration['dbname'] = $name;
    }

    /**
     * @method ConfigurationInterface setPass
     * @param string $pass
     * @return void
     */
    public function setPass(string $pass) : void
    {
        $this->configuration['pass'] = $pass;
    }

    /**
     * @method ConfigurationInterface setDriver
     * @param string $driver
     * @return void
     */
    public function setDriver(string $driver) : void
    {
        $this->configuration['driver'] = $driver;
    }

    /**
     * @method ConfigurationInterface setOther
     * @param array $other
     * @return void
     */
    public function setOther(array $other) : void
    {
        foreach ($other as $key => $value) :
            // create configuration key with a value
            $this->configuration[$key] = $value;
        endforeach;
    }

    /**
     * @method ConfigurationInterface getHost
     * @return string
     */
    public function getHost() : string
    {
        return isset($this->configuration['host']) ? $this->configuration['host'] : '';
    }

    /**
     * @method ConfigurationInterface getUser
     * @return string
     */
    public function getUser() : string
    {
        return isset($this->configuration['user']) ? $this->configuration['user'] : '';
    }

    /**
     * @method ConfigurationInterface getName
     * @return string
     */
    public function getName() : string
    {
        return isset($this->configuration['name']) ? $this->configuration['name'] : '';
    }

    /**
     * @method ConfigurationInterface getPass
     * @return string
     */
    public function getPass() : string
    {
        return isset($this->configuration['pass']) ? $this->configuration['pass'] : '';
    }

    /**
     * @method ConfigurationInterface getDriver
     * @return string
     */
    public function getDriver() : string
    {
        return isset($this->configuration['driver']) ? $this->configuration['driver'] : '';
    }

    /**
     * @method ConfigurationInterface getOther
     * @param string $title
     * @return string
     */
    public function getOther(string $title) : string
    {
        return isset($this->configuration[$title]) ? $this->configuration[$title] : '';
    }

    /**
     * @method ConfigurationInterface getConfiguration
     * @return array
     */
    public function getConfiguration() : array
    {
        return $this->configuration;
    }
}