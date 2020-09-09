<?php
namespace Lightroom\Database;

use ArrayAccess;
/**
 * @package QueryFetchRow
 * @author Amadi Ifeanyi
 * 
 * This class provides amazing flexibility for database rows. It handles every row as an object with 
 * capability of doing more.
 */
class QueryFetchRow implements ArrayAccess
{
    /**
     * @var Interfaces\QueryBuilderInterface $___queryBuilder
     */
    private $___queryBuilder;

    /**
     * @var array $dd (data dump)
     * Holds a copy of the row as an array
     */
    private $dd = [];

    /**
     * @method QueryFetchRow __construct
     * @param Interfaces\QueryBuilderInterface $builder
     * @param mixed $row
     */
    public function __construct(&$builder, $row)
    {
        // hold a copy
        $this->dd = is_object($row) ? func()->toArray($row) : $row;

        // hold a reference of builder
        $this->___queryBuilder =& $builder;
    }

    /**
     * @method QueryFetchRow __get
     * @method string $column
     *
     * This method returns a value of a column
     * @param string $column
     * @return mixed
     */
    public function __get(string $column)
    {
        // get column from held array
        if (isset($this->dd[$column])) :
        
            return $this->dd[$column];

        endif;

        // return from query builder.
        return $this->___queryBuilder->{$column};
    }

    /**
     * @method QueryFetchRow __call
     * @param string $method
     * @param array $arguments
     * @return mixed
     * 
     * This requests for a method from the query builder.
     */
    public function __call(string $method, array $arguments)
    {
        // update packed array  
        $this->___queryBuilder->getPacked = $this->dd;

        // make query
        return call_user_func_array([$this->___queryBuilder, $method], $arguments);
    }

    /**
     * @method ArrayAccess offsetExists
     * @param string $column
     * @return bool
     * 
     * Check if a column exists.
     */
    public function offsetExists($column)
    {
        // @var bool $offsetExists
        $offsetExists = false;

        // check if offset exists
        if (isset($this->dd[$column])) :
        
            // update offsetExists
            $offsetExists = true;

        endif;

        // return bool
        return $offsetExists;
    }

    /**
     * @method ArrayAccess offsetGet
     * @param string $column
     * @return mixed
     * 
     * Returns the value of a column
     */
    public function offsetGet($column)
    {
        // @var mixed $value
        $value = null;

        // check if column exists
        if ($this->offsetExists($column)) :
        
            $value = $this->dd[$column];

        endif;

        // return mixed
        return $value;
    }

    /**
     * @method ArrayAccess offsetSet
     * @param string $column
     * @param mixed $value
     * @return void
     * 
     * Sets a value with a key or create if it doesn't exists
     */
    public function offsetSet($column, $value)
    {
        $this->dd[$column] = $value;
    }

    /**
     * @method ArrayAccess offsetUnset
     * @param string $column
     * @return void
     * 
     * Remove a column from a row.
     */
    public function offsetUnset($column)
    {
        if ($this->offsetExists($column)) :
            
            // remove column
            unset($this->dd[$column]);

        endif;
    }
}