<?php
namespace Lightroom\Database\Promises;

use PDO, PDOStatement;
use Lightroom\Database\Interfaces\{
    DriverInterface, QueryPromiseInterface
};
use Lightroom\Database\QueryFetchRow;
use Lightroom\Database\Promises\QueryPromise\{
    PackMethods, Properties, QueryMethods
};
use ReflectionException;

/**
 * @property string|null getPacked
 * @property string|null rows
 * @property QueryPromise usingJoin
 * @package QueryPromise
 * @author Amadi Ifeanyi <amadiify.com>
 */
class QueryPromise implements QueryPromiseInterface
{
    // fetch methods..
    private static $fetchMethods = [];

    // use properties
    use Properties;

    // config
    public function config(array $config)
    {
        $this->configData = $config;
        
        return $this;
    }

    /**
     * @method QueryPromiseInterface getFetchMethods
     * @return array
     * 
     * Get fetch methods for query builder.
     */
    public static function getFetchMethods() : array
    {
        // return array
        return [
            'named'     => PDO::FETCH_NAMED,
            'assoc'     => PDO::FETCH_ASSOC,
            'array'     => PDO::FETCH_ASSOC,
            'lazy'      => PDO::FETCH_LAZY,
            'num'       => PDO::FETCH_NUM,
            'both'      => PDO::FETCH_BOTH,
            'obj'       => PDO::FETCH_OBJ,
            'bound'     => PDO::FETCH_BOUND,
            'column'    => PDO::FETCH_COLUMN,
            'class'     => PDO::FETCH_CLASS,
            'into'      => PDO::FETCH_INTO,
            'group'     => PDO::FETCH_GROUP,
            'unique'    => PDO::FETCH_UNIQUE,
            'func'      => PDO::FETCH_FUNC,
            'keypair'   => PDO::FETCH_KEY_PAIR,
            'classtype' => PDO::FETCH_CLASSTYPE,
            'serialize' => PDO::FETCH_SERIALIZE,
            'propslate' => PDO::FETCH_PROPS_LATE
        ];
    }

    /**
     * @method QueryPromiseInterface hasFetchMethod
     * @param string $method
     * @return bool
     */
    public static function hasFetchMethod(string $method) : bool
    {
        // @var bool $hasMethod
        $hasMethod = false;

        // check here
        if (isset(self::getFetchMethods()[$method])) $hasMethod = true;

        // return bool
        return $hasMethod;
    }

    // static set function
    public function set($name, $data)
    {
        if ($name == 'getPacked')
        {
            $this->getPacked = $data;
        }
        else
        {
            $this->{$name} = $data;
        }
    }

    // Clean up after use.
    public static function cleanUp($promise)
    {
        $promise->errors = [];
        $promise->hasError = false;
    }

    // set pdo statement
    public function setPdoStatement(PDOStatement $statement) : void
    {
        $this->pdoStatement = $statement;
    }

    // return the pdO statement
    public function pdo() : PDOStatement 
    {
        return $this->pdoStatement;
    }

    // return the row count
    public function rowCount() : int 
    {
        return $this->pdoStatement->rowCount();
    }

    // return a row
    public function fetch($method = PDO::FETCH_ASSOC) 
    {
        return $this->pdoStatement->fetch($method);
    }

    // return rows
    public function fetchAll($method = PDO::FETCH_ASSOC) 
    {
        return $this->pdoStatement->fetchAll($method);
    }

    // send
    public function send()
    {
        return $this;
    }

    public function __call(string $method, array $args)
    {
        // @var array $fetchMethods
        $fetchMethods = self::getFetchMethods();

        // return value
        $returnValue = false;

        // Check if $method is part of the fetch methods
        if (isset($fetchMethods[$method])) :
        
            // get configuration is sent
            $config = isset($args[0]) ? $args[0] : [];

            // get if $config is a closure function
            if (is_callable($config)) :
            
                // reset pointer
                $this->reset();

                while($fetch = $this->___fetch($fetchMethods[$method], $method)) :
                
                    call_user_func($config, $fetch);

                endwhile;

                $fetch = null;

            else :
                // return fetch result
                $returnValue = $this->___fetch($fetchMethods[$method], $method, $config);
            endif;

        endif;

        // return mixed
        return $returnValue;
    }

    /**
     * @method QueryPromise reset
     * @return void
     * 
     * Resets a fetch pointer
     */
    public function reset() : void
    {
        self::$loopid = 0;
        $this->_loopid = 0;
    }

    // get magic method
    public function __get(string $name)
    {
        // @var array $packed
        $packed = $this->getPacked;

        // return data
        $returnData = null;

        // get from packed
        if (isset($packed[$name])) :
        
            $returnData = stripslashes($packed[$name]);
        
        elseif (method_exists($this, $name)) :
        
            $returnData = $this->{$name}();
        
        else:
        
            $this->errors[] = $name.' doesnt exists.';
            $this->hasError = true;

        endif;

        // return value
        return $returnData;
    }

    // set bind data
    public function setBindData($bind)
    {
        $this->bindData = $bind;
    }

    /**
     * @method QueryPromise go
     * @return QueryPromise
     */
    public function go()
    {
        return $this;
    }

    // hasRows method
    public function hasRows($response, $failed = null)
    {
        // check if we have rows
        if ($this->rows > 0) :
        
            if (is_callable($response) || function_exists($response)) :
            
                return call_user_func($response, $this);

            endif;

            return $response;

        endif;

        return (!is_null($failed) && is_callable($failed) ? call_user_func($failed, $this) : $failed);
    }

    // has single row method
    public function hasRow(...$arguments)
    {
        return call_user_func_array([$this, 'hasRows'], $arguments);
    }

    /**
     * @method QueryPromise join
     * @param object $object (optional)
     * @return object
     * 
     * This method joins the current promise to an object
     */
    public function join($object = null)
    {
        if ($object === null) :
        
            // get current instance
            $currentInstance =& $this;

            // create a new instance
            $newInstance = new self;

            // set get packed
            $newInstance->getPacked = $currentInstance->getPacked;
            $newInstance->bindData = $currentInstance->bindData;
            $newInstance->pdoStatement = $currentInstance->pdoStatement;
            $newInstance->usingJoin = $currentInstance;
        
        else:
        
            $newInstance = $this;

            if (is_object($object) && get_class($object) == static::class) :
            
                if ($object->rows > 0) :
                
                    $packed = $object->getPacked;
                    $newInstance->getPacked = array_merge($newInstance->getPacked, $packed);

                endif;

            endif;

        endif;

        // return a new instance
        return $newInstance;
    }

    /**
     * @method QueryPromise loadPromise
     * @param mixed $statement (finished or unfinished)
     * @return mixed
     *
     * This method returns a promise for a builder class or a pdo statement.
     * @throws ReflectionException
     */
    public static function loadPromise($statement)
    {
        // we received the pdo statement here, so we exclude the query methods
        $instance = new class() extends QueryPromise {
            use PackMethods;
        };

        // we didn't receive a pdo statement here, so we include the query methods
        if (get_class($statement) != PDOStatement::class) :

            $instance = new class() extends QueryPromise {
                use PackMethods, QueryMethods;
            };

        endif;

        // return promise
        return $instance->preparePromise($statement);
    }

    /**
     * @method QueryPromise preparePromise
     * @param mixed $statement
     * @return QueryPromise
     *
     * This method prepares the promise handler. It takes an object, determines if it's a pdo statement or a builder class,
     * It extracts some properties from the builder class and finally executes the builder class, then returns a promise
     * @throws ReflectionException
     */
    protected function preparePromise($statement) : QueryPromise
    {
        // @var string $method
        $method = 'sql';

        // make ready PDOStatement
        switch(get_class($statement) != PDOStatement::class) :

            case true:

                // set fetch mode
                $this->setFetchMode();

                // get method
                $method = $statement->method;

                // execute statement
                $execute = method_exists($statement, 'go') ? $statement->go() : $statement;

                // create reflection class
                $reflection = new \ReflectionClass($statement);

                // get properties
                $properties = [];

                // loop through
                foreach ($reflection->getProperties() as &$property) :

                    $properties[$property->name] = null;

                    // clean up
                    unset($property);

                endforeach;

                // clean up
                unset($reflection);

                // set allow slashes from builder
                $this->allowSlashes = array_key_exists('allowSlashes', $properties) ? $statement->allowSlashes : true;

                // set bind data from builder
                if (array_key_exists('method', $properties) && array_key_exists('bind', $properties)) :

                    // set bind data
                    if ($method != 'select') $this->setBindData($statement->bind);

                     // add last id
                    if ($method == 'insert') : 

                        // get last inserted id
                        $this->id = array_key_exists('pdoInstance', $properties) ? $statement->pdoInstance->lastInsertId() : $this->id;

                        // try get if id == 0
                        if ($this->id == '0' || $this->id == null) :

                            // @var array $argumentPasssed
                            $argumentPasssed = $statement->getArgumentsPassed();

                            // set the table
                            $query = $statement->table($statement->table)->resetBuilder();

                            // make query
                            $query = self::loadPromise(call_user_func_array([$query, 'get'], $argumentPasssed));

                            // set the id 
                            $this->id = $query->primary();

                        endif;

                    endif;

                    // remove bind and query
                    $statement->bind = []; $statement->query = '';

                endif;

                 // set table
                $this->table = array_key_exists('table', $properties) ? $statement->table : $this->table;

                // reset builder
                array_key_exists('getSql', $properties) ? $statement->getSql = '' : null;
                array_key_exists('getBinds', $properties) ? $statement->getBinds = [] : null;
                array_key_exists('allowed', $properties) ? $statement->allowed = $statement->getAllowed() : null;

                // set database instance
                $this->setDatabaseInstance($statement);

                // update statement
                $statement = $execute;

                // clean up
                unset($properties);

            break;

            case false:

                // update 
                $this->getPacked = $statement->fetch(PDO::FETCH_ASSOC);

            break;

        endswitch;

        // set statement
        $this->setPdoStatement($statement);

        // set rows
        $this->rows = $this->row = $statement->rowCount();

        // set packed
        if ($this->row == 1 && $method == 'select') :

            // push to pack
            $this->getPacked = $statement->fetch(PDO::FETCH_ASSOC);

        endif;


        // return instance
        return $this;
    }

    /**
     * @method QueryPromise __fetch
     * @param string $fetchMode
     * @param string $name
     * @param array $config
     * @return mixed
     */
    private function ___fetch(string $fetchMode, string $name, array $config = [])
    {
        // reset pointer
        if (self::$loopid !== null) :
        
            // set loop id
            $this->_loopid = 0;
            self::$loopid = null;

        endif;

        if ($this->pdoStatement != null)
        {
            //@var int $pointer
            $pointer = $this->_loopid;
            static $all = [];

            // pointer
            if ($pointer == 0) :
            
                $this->pdoStatement->execute();
                $this->_rows = $this->pdoStatement->rowCount();

                // fetch with a class
                if ($name == 'class') :
                
                    // fallback config
                    $config = is_string($config) ? $config : get_class($this);

                    // get all
                    while($class = $this->pdoStatement->fetchAll($fetchMode, $config)) $all = $class;
                    
                
                // fetch with a function
                elseif ($name == 'func') :
                    
                    // fallback config
                    if (is_array($config) && count($config) == 0) $config = [$this, '__fetchFunc'];

                    // get all
                    while($func = $this->pdoStatement->fetchAll($fetchMode, $config)) $all = $func;
                
                // fetch into.
                elseif ($name == 'into') :
                
                    // fallback config
                    $config =  is_string($config) ? $config : get_class($this);
                    $config = new $config;

                    // set fetch mode
                    $this->pdoStatement->setFetchMode($fetchMode, $config);

                    // get all
                    foreach ($this->pdoStatement as &$into) $all[] = $into;

                    // clean
                    $into = null;
                
                // fetch by bound and key pair
                elseif ($name == 'bound' || $name == 'keypair') :
                
                    // @var array $bound
                    $bound = [];

                    // set fetch mode
                    $this->pdoStatement->setFetchMode($fetchMode, $bound);

                    // get all
                    foreach (self::$pdo as &$data) $all[] = $data;

                    // clean
                    $data = null;

                endif;

            endif;


            // fetch from all
            if (count($all) > 0) $row = $all[$pointer];


            // fetch records
            if ($name != 'class' && $name != 'func'  && $name != 'into'  && $name != 'bound' && $name != 'keypair') :
            
                // get row
                $row = $this->pdoStatement->fetch($fetchMode);

            endif;


            // replace encoded entities
            if (is_array($row)) :
            
                // update $row. Decode entity and remove slashes.
                foreach ($row as $column => &$value) :

                    // remove slashes if not allowed
                    if (!$this->allowSlashes) $value = stripslashes($value);

                    // update row and decode entity
                    $row[$column] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

                endforeach;

                // clean up
                $value = null;
            
            elseif (is_object($row)) :
    
                // update $row. Decode entity and remove slashes.
                foreach ($row as $column => &$value) :

                    // remove slashes if not allowed
                    if (!$this->allowSlashes) $value = stripslashes($value);

                    // update row and decode entity
                    $row->{$column} = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

                endforeach;
            
            endif;
            

            // kill loop only if pointer is equivalent to the number of rows returned
            if ($pointer == $this->_rows) :
            
                // reset pointer
                $pointer = 0;

                // reset loop id
                $this->_loopid = 0;

                // reset fetch records
                $this->fetch_records = null;

                // stop process
                return false;

            endif;

            // update loop id
            $this->_loopid++;

            if (method_exists($this, 'get')) :

                // return query fetch row class
                return new QueryFetchRow($this, $row);

            else:

                return $row;

            endif;
        }
        
        return false;
    }
}