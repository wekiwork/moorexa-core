<?php
namespace Lightroom\Database;

use Lightroom\Database\Interfaces\{
    ChannelInterface, QueryBuilderInterface
};
/**
 * @package DatabaseChannel
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * Provides an easy way to channel database requests.
 */
class DatabaseChannel implements ChannelInterface
{
    /**
     * @var string $query
     */
    private string $query = '';

    /**
     * @var string $table
     */
    private string $table = '';

    /**
     * @var string $method
     */
    private string $method = '';

    /**
     * @var string $origin
     */
    private string $origin = '';

    /**
     * @var array $bind
     */
    private array $bind = [];

    /**
     * @var QueryBuilderInterface $builder
     */
    private QueryBuilderInterface $builder;

    /**
     * @var array $instances
     */
    private static array $instances = [];

    /**
     * @method ChannelInterface getTable
     * @return string
     */
    public function getTable() : string
    {
        return $this->table;
    }

    /**
     * @method ChannelInterface getBind
     * @return array
     */
    public function getBind() : array
    {
        return $this->bind;
    }

    /**
     * @method ChannelInterface setBind
     * @param array $bind
     * @return void
     */
    public function setBind(array $bind) : void
    {
        $this->bind =& $bind;
    }

    /**
     * @method ChannelInterface getQuery
     * @return string
     */
    public function getQuery() : string
    {
        return $this->query;
    }   

    /**
     * @method ChannelInterface setQuery
     * @param string $query
     * @return void
     */
    public function setQuery(string $query) : void
    {
        $this->query =& $query;
    }

    /**
     * @method ChannelInterface getMethod
     * @return string 
     */
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * @method ChannelInterface getOrigin
     * @return string 
     */
    public function getOrigin() : string
    {
        return $this->origin;
    }

    /**
     * @method ChannelInterface getBuilder
     * @return QueryBuilderInterface 
     */
    public function getBuilder() : QueryBuilderInterface
    {
        return $this->builder;
    }

    /**
     * @method ChannelInterface setBuilder
     * @param QueryBuilderInterface $builder
     * @return void
     */
    public function setBuilder(QueryBuilderInterface $builder) : void
    {
        $this->builder =& $builder;
    }

    /**
     * @method ChannelInterface loadInstance
     * @param string $driver
     * @param array $properties
     * @return DatabaseChannel
     */
    public static function loadInstance(string $driver, array $properties) : DatabaseChannel
    {
        // @var DatabaseChannel $instance 
        $instance = null;

        if (isset(self::$instances[$driver])) :

            // get instance
            $instance = self::$instances[$driver];

        else:

            // create instance
            $instance = new DatabaseChannel;

            // cache
            self::$instances[$driver] = $instance;

        endif;

        // reset properties
        $instance->resetProperties($properties);

        // return instance
        return $instance;
    }

    /**
     * @method DatabaseChannel resetProperties
     * @param array $properties
     * @return void
     */
    private function resetProperties(array $properties) : void 
    {
        // reset all
        $this->table = '';
        $this->query = '';
        $this->bind = [];
        $this->method = '';

        // load properties
        foreach ($properties as $property => $value) :

            // check if property exists
            if (property_exists($this, $property)) $this->{$property} = $value;

        endforeach;
    }
}