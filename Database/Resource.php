<?php
namespace Lightroom\Database;

use Closure;
/**
 * @package Database Result Resource Manager
 * @author Amadi Ifeanyi
 */
class Resource
{
    /**
     * @var array $queryData
     */
    private $queryData = [];

    /**
     * @var string $returnType
     */
    private $returnType = '';

    /**
     * @method Resource loadData
     * @param array $data
     * @param string $returnType 
     * @return void
     */
    public function loadData(array $data, string $returnType = 'array') : void 
    {
        $this->queryData = $data;
        $this->returnType = $returnType;
    }

    /**
     * @method Resource isLoaded
     * @return bool
     */
    public function isLoaded() : bool 
    {
        return (count($this->queryData) > 0 ? true : false);
    }

    /**
     * @method Resource loadCallback
     * @param Closure $callback
     * @return mixed
     */
    public function loadCallback(Closure $callback) 
    {
        // create a temp class
        $tempClass = new class($this->queryData)
        {
            /**
             * @var array $data 
             */
            private $data = [];

            /**
             * @method Resource __construct
             * @param array $data
             * @return void
             */
            public function __construct($data) { $this->data = $data; }

            /**
             * @method Resource set
             * @param string $key
             * @param mixed $value
             * @return object
             */
            public function set(string $key, $value) { $this->data[$key] = $value; }

            /**
             * @method Resource get
             * @param string $key
             * @return mixed
             */
            public function get(string $key) { return (isset($this->data[$key]) ? $this->data[$key] : null); }

            /**
             * @method Resource __set
             * @param string $key
             * @param mixed $value
             * @return object
             */
            public function __set(string $key, $value) { $this->set($key, $value); }

            /**
             * @method Resource __get
             * @param string $key
             * @return mixed
             */
            public function __get(string $key) { return $this->get($key); }

            /**
             * @method Resource __loadAll
             * @return array
             */
            public function __loadAll() : array { return $this->data; }
        };

        // call data
        call_user_func($callback->bindTo($tempClass, \get_class($tempClass)));

        // load all data
        $data = $tempClass->__loadAll();

        // convert to a specific type
        return $this->returnType == 'object' ? func()->toObject($data) : $data;
    }
}