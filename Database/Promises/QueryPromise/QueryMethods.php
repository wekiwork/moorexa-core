<?php
namespace Lightroom\Database\Promises\QueryPromise;

use Exception;
use Lightroom\Exceptions\ClassNotFound;
use PDOStatement, PDO;
use Lightroom\Adapter\ClassManager;
use function Lightroom\Database\Functions\map;
use Lightroom\Database\Interfaces\{
    DriverInterface, QueryPromiseInterface
};

/**
 * @package QueryMethods
 */
trait QueryMethods
{
    /**
     * @var string $identity
     */
    private $identity = '';

    /**
     * @var bool $usingJoin
     */
    private $usingJoin = false; 

    /**
     * @var object $databaseInstance
     */
    protected $databaseInstance = null;

    /**
     * @method QueryMethods pick
     * @param mixed ...$arguments
     *
     * This method takes a list of columns and returns its values
     * @return array|QueryMethods
     */
    public function pick(...$arguments)
    {
        // return value
        $returnValue = &$this;

        // check size of arguments
        if (count($arguments) > 1) :
        
            // @var array $array
            $array = [];

            // execute request
            $executeRequest = $this->get();

            foreach ($arguments as $argument) :

                // update array
                $array[$argument] = $executeRequest->{$argument};

            endforeach;

            // update return value
            $returnValue = $array;

            // clean up
            unset($array, $executeRequest);

        endif;
        
        // update return value
        if (count($arguments) == 1) $returnValue = $this->row()->{$arguments[0]};

        // return value
        return $returnValue;
    }

    /**
     * @method QueryMethods from
     * @param string $tableName
     * @param string $identity
     *
     * This method attempts to fetch a record from another table with the data from the current table
     * @return QueryMethods
     */
    public function from(string $tableName, string $identity='')
    {
        // @var QueryMethods $promise
        $promise = $this;

        // get table name
        $promise->table = $this->databaseInstance->getTableName($tableName);

        // query using join
        if ($this->usingJoin === false) :
        
            // create a new db instance
            $promise = new self;

            // update promise
            $promise->table = $this->databaseInstance->getTableName($tableName);
            $promise->getPacked = $this->getPacked;
            $promise->bindData = $this->bindData;
        
        endif;

        // set identity
        $promise->identity = $identity != '' ? $identity : $promise->identity;

        // set database instance
        $promise->databaseInstance = $this->databaseInstance;

        // return instance
        return $promise;
    }

    /**
     * @method QueryMethods identity
     * @param string $column
     * @return QueryMethods
     * 
     * This method sets the identity for a query. It's majorly used by the from() method.
     */
    public function identity(string $column) 
    {
        // set identity
        $this->identity = $column;

        // return instance
        return $this;
    }

    /**
     * @method QueryMethods update
     * @param array $data
     * @param string $where
     * @param string $other
     * @return PDOStatement
     *
     * This method updates a row with its primary key
     * @throws ClassNotFound
     */
    public function update(array $data=[], $where=null, $other=null) : QueryPromiseInterface
    {
        // get builder
        $builder = $this->databaseInstance->resetBuilder();

        // get return statement
        $statement = ClassManager::singleton(PDOStatement::class);

        if ($where === null) :
        
            // get row
            $row = $this->row();

            if ($row !== null) :
            
                // get primary
                $primary = $this->identity;

                // get primary from table info method
                if ($this->identity == null) $this->getTableInfo($primary);

                // update statement
                $statement = map($builder->table($this->table)->config($this->configData)->update($data, $primary . ' = ?', ($row->{$primary})));

            endif;

        else:

            if ($other === null) :
                
                // update statement
                $statement = map($builder->table($this->table)->config($this->configData)->update($data, $where));
            
            else:
                
                // update statement
                $statement = map(call_user_func_array([
                    
                    // prepare table
                    $builder->table($this->table)->config($this->configData), 

                    // query method
                    'update'

                ], func_get_args()));

            endif;
        
        endif;

        // return statement
        return $statement;
    }

    /**
     * @method QueryMethods insert
     * @param array $data
     * @return PDOStatement
     * 
     * This method inserts a row into the current table
     */
    public function insert(array $data=[]) : QueryPromiseInterface
    {
        // @var PDOStatement $table
        $table = $this->databaseInstance->resetBuilder()->table($this->table)->config($this->configData);

        // insert a row
        return map(call_user_func_array([$table, 'insert'], func_get_args()));
    }

    /**
     * @method QueryMethods get
     * @param mixed $rowid 
     * @param mixed $where
     * @return PDOStatement
     * 
     * This method run will run a get query on the current table. The primary id can be
     * extended to another table as a reference id
     */
    public function get($rowid=null, $where=null) : QueryPromiseInterface
    {
        // get row
        $row = $this->row();

        // @var string $primary
        $primary = $this->identity;

        // @var int $primaryid
        $primaryid = 0;

        // get primary from table info method
        if ($this->identity == null) $this->getTableInfo($primary);

        // try get primaryid
        if ($row === null && $rowid === null) : 
        
            // get primary id from bind data
            if (isset($this->bindData[$primary])) :
                // update primaryid
                $primaryid = intval($this->bindData[$primary]);
            endif;
        
        elseif ($row !== null):
        
            // update primaryid
            $primaryid = $rowid;

            // check if primary field exists in row
            if (isset($row->{$primary})) :
                // update primaryid
                if ($rowid === null or !is_int($rowid)) $primaryid = $row->{$primary};
            endif;

        endif;

        // get rowid
        $rowid = is_null($rowid) ? intval($primaryid) : $rowid;

        // get builder
        $builder = $this->databaseInstance->resetBuilder();


        // get return statement
        $statement = &$this;

        if (is_int($rowid)) :

            // run get
            $statement = map($builder->table($this->table)->get($primary . ' = ?', $primaryid));
        
        elseif (is_string($rowid) || is_array($rowid)) :
        
            // get function arguments
            $args = func_get_args();

            // build query
            if (isset($args[0]) and is_string($args[0])) :
            
                
                if (strpos($rowid, '=') === false) :

                    // add where statement
                    if (isset($args[1])) :

                        $last = $args[1];

                        if (is_string($last)) :

                            // add primary id
                            $args[1] .= ' AND ' . $primary . ' = ?';

                            // add primary id
                            array_push($args, $primaryid);

                        elseif (is_array($last)) :

                            // add primary id
                            $args[1][$primary] = $primaryid;

                        endif;  

                    else:

                        // add primary key
                        $args[1] = [$primary => $primaryid];

                    endif;

                endif;

            endif;

            // update statement
            $statement = map(call_user_func_array([$builder->table($this->table), 'get'], $args));

        endif;

        // check if join method was used
        if ($this->usingJoin !== false) :
            
            if ($statement->rows > 0) :
            
                // get packed row
                $packed = $statement->getPacked;

                // merge getpacked and packed data
                $this->usingJoin->getPacked = array_merge($this->usingJoin->getPacked, $packed);

                // clean up
                $packed = null;

            endif;

            // update statement
            $statement = $this->usingJoin;

        endif;

        // return QueryPromiseInterface
        return $statement;
    }

    /**
     * @method QueryMethods pop
     * @param int $primaryid
     * @return PDOStatement
     * 
     * This method will run a delete query on the current row or on a primary key
     */
    public function pop(int $primaryid=0) : QueryPromiseInterface
    {
        // get row
        $row = $this->row();

        // get primary field
        $primary = $this->identity;

        // get primary from table info method
        if ($this->identity == null) $this->getTableInfo($primary);

        // get database instance
        $builder = $this->databaseInstance->resetBuilder();

        if ($primaryid !== 0) :
        
            return map($builder->table($this->table)->config($this->configData)->delete($primary . ' = ?', $primaryid));
        
        elseif ($primaryid === 0) :
        
            return map($builder->table($this->table)->config($this->configData)->delete($primary . ' = ?', ($row->{$primary})));
        
        else:

            return map(call_user_func_array([$builder->table($this->table)->config($this->configData), 'delete'], func_get_args()));

        endif;
        
    }

    /**
     * @method QueryMethods getTableInfo
     * @param string $primary 
     * @return void
     * 
     * This method by reference, returns the primary field of the current working table
     */
    public function getTableInfo(string &$primary = '') : void
    {
        // @var string $driverNamespace
        $driverNamespace = rtrim($this->databaseInstance->getDriverNamespace(), 'Driver');

        // get primary field
        $primary = ClassManager::singleton($driverNamespace . 'Table')->getPrimaryField($this->table);
    }

    /**
     * @method QueryMethods query
     * @param mixed $loadFrom (could either be a string or array)
     * @param array $arguments
     * @return mixed|QueryMethods
     * @throws Exception
     */
    public function query($loadFrom, ...$arguments)
    {
        $classCaller = null;

        switch (gettype($loadFrom))
        {
            case 'string':
                // build method name
                $method = 'query'.ucwords($loadFrom);
            break;

            case 'array':
                // get class name and method.
                list($className, $method) = $loadFrom;
                $method = 'query'.ucwords($method);

                // check class name
                $classCaller = $className; // here we assume $className is an object

                // but let's check if it's a string
                if (is_string($classCaller)) $classCaller = BootMgr::singleton($classCaller);

            break;
        }

        // check caller class
        if ($classCaller == null) throw new Exception('Query class not found for method "'.$method.'"');

        // get arguments
        $args = array_splice($arguments, 1);
        array_unshift($args, $this);

        // check if method exists
        if (method_exists($classCaller, $method)) return call_user_func_array([$classCaller, $method], $args);

        // return instance
        return $this;
    }
 
    /**
     * @method QueryMethods primary
     * @return mixed
     * 
     * This method returns the primary key value
     */
    public function primary()
    {
        static $tableInfo;

        // @var string $primarykey
        $primarykey = null;

        // @var string $primary
        $primary = '';

        if (!isset($tableInfo[$this->table])) :
        
            $tableInfo = [];

            // get primary key
            $this->getTableInfo($primary);

            // set primary key
            $tableInfo[$this->table] = $primary;

        endif;

        // return primary key
        if (isset($tableInfo[$this->table])) :
        
            $primary = $tableInfo[$this->table];

            // return primary key
            $primarykey = $this->{$primary};

        endif;

        // return mixed
        return $primarykey;
    }

    /**
     * @method QueryMethods lastQuery
     * @return mixed
     * 
     * This method returns the last query ran.
     */
    public function lastQuery()
    {
        // get last query ran
        return !is_null(self::$lastQueryRan) ? self::$lastQueryRan : $this;
    }

    /**
     * @method QueryPromise
     * @param object $instance
     * @return void
     * 
     * This method registers the current working database instance.
     */
    public function setDatabaseInstance($instance) : void
    {
        $this->databaseInstance = $instance; 
    }

    /**
     * @method QueryMethods reduce
     * @param mixed $column (closure or string)
     * @return int
     * 
     * This method reduces a column
     */
    public function reduce($column) : int
    {
        // @var int $reduce
        $reduce = 0;

        /// check if $column is a closure
        if (is_callable($column)) :

            $this->obj(function($row) use ($column, &$reduce)
            {
                $reduce += call_user_func($column, $row);
            });
            
        // $column is a string
        elseif (is_string($column)) :

            $this->obj(function($row) use (&$reduce, $column)
            {
                if ($row->has($column)) $reduce += $row->{$column};
            });

        endif;

        // return 
        return $reduce;
    }
}