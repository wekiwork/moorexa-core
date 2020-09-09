<?php
namespace Lightroom\Database;

use PDO;
use Lightroom\Database\{
    DatabaseHandler as Handler, Cache\FileQueryCache, 
    Interfaces\QueryBuilderInterface
};
use ReflectionException;
use Lightroom\Exceptions\{
    QueryBuilderException, ClassNotFound
};
use Lightroom\Database\Queries\{
    Delete as deleteStatement,
    Insert as insertStatement,
    Update as updateStatement,
    Select as selectStatement,
    Sql    as sqlStatement,
    QueriesHelper
};
use Lightroom\Adapter\ClassManager;


/**
 * @package QueryBuilder
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is an extensive query builder for all relational databases
 */
trait QueryBuilder
{
    // load properties
    use QueryBuilderProperties, QueryBuilderMagicMethods, QueryBuilderExecute, 
    QueriesHelper, TablePrefixing, FileQueryCache;

    // load statements
    use deleteStatement, insertStatement, selectStatement, updateStatement, sqlStatement;

    /**
     * @method QueryBuilder __call
     * @param string $method
     * @param array $arguments
     * @return QueryBuilder|mixed
     * @throws QueryBuilderException
     */
    public function __call(string $method, array $arguments)
    {
        
        switch ($method) :

            // table name
            case 'table':
                
                // use no prefix
                if (defined('NO_PREFIX')) :
                    
                    // check if $arguments[1] == NO_PREFIX then turn of prefixing
                    if (isset($arguments[1]) && $arguments[1] == NO_PREFIX) $this->noPrefix = true;

                endif;

                // get table
                $table = $arguments[0];

                // check for >
                if (strpos($table, '>') !== false) :

                    // tableArray
                    $tableArray = explode('>', $table);

                    // get table
                    $table = trim($tableArray[0]);

                    // set aliase
                    $this->tableAlaise = ' ' . trim($tableArray[1]);

                endif;

                // set table name
                $this->table = $this->getTableName($table);
            break;

            // sql statement
            case 'sql':
                return $this->callMethod('sqlStatement', $arguments)->go();

            // allow html in sql statement, default is false
            case 'allowHTML':
                $this->allowHTMLTags = true;
            break;

            // allow slashes in sql statement, default is false
            case 'allowSlashes':
                $this->allowSlashes = true;
            break;

            // add binds
            case 'bind':
                return $this->callMethod('runBinding', $arguments);

            // add where statement
            case 'where':
                return $this->callMethod('runWhere', $arguments);

            // add 'or' to where statement
            case 'orWhere':
                $this->wherePrefix = ' or ';
                return $this->callMethod('runWhere', $arguments);

            // add 'and' to where statement
            case 'andWhere':
                $this->wherePrefix = ' and ';
                return $this->callMethod('runWhere', $arguments);

            // check if table has rows
            case 'hasRow':
            case 'hasRows':
                // execute request
                return call_user_func_array([$this->go(), $method], $arguments);

            // manage crud requests
            case 'get':
            case 'select':
            case 'delete':
            case 'update':
            case 'insert':
                 // replace method 'get' with 'select'
                 $method = $method == 'get' ? 'select' : $method;

                 // get statement for this method
                 $statement = $this->statements()[$method];

                 // find placeholder replacement
                 if (isset($this->placeholderReplacement['{table}'])) :

                    // replace table placeholder in statement 
                    $statement = str_replace('{table}', '{table}, ' .implode(',', $this->placeholderReplacement['{table}']), $statement);

                 endif;

                 // update current method
                 $this->method = $method;

                 // replace '{table}' from statement
                 if (strlen($this->table) > 1) $statement = str_replace('{table}', $this->table . $this->tableAlaise, $statement);

                 // return QueryBuilderInterface
                 return $this->callMethod($method . 'Statement', [$arguments, $statement]);

            break;

            default:

                if (isset($this->allowed[$method])) :
                
                    // get allowed methods
                    $allowed = $this->getAllowed($arguments, $this->query);

                    // append to query
                    $this->query .= is_callable($allowed[$method]) ? call_user_func_array($allowed[$method]->bindTo($this, static::class), $arguments) : $allowed[$method];

                else:

                    // @var array $newBind
                    $newBind = [];

                    // @var array $bind
                    $bind = [$method => ''];

                    // get index
                    $index = $this->__avoidClashes($bind, $newBind);

                    // specifically where..
                    if (preg_match('/({where})/', $this->query)) :
                        
                        // add $method to where statement
                        $where = 'WHERE '.$method.' = :'.$method.$index.' ';

                        // update query
                        $this->query = str_replace('{where}', $where, $this->query);

                    else:

                        // append $method to query
                        $append = ' '.$method.' = :'.$method.$index.' ';

                        // update query
                        $this->query = trim($this->query) . $append;

                    endif;

                    // update bind
                    $this->bind = array_merge($this->bind, $newBind);

                endif;

        endswitch;

        return $this;
    }

    /**
     * @method QueryBuilder orderByPrimaryKey
     * @param string $mode
     * @return QueryBuilder
     *
     * This runs order by primary key with a sorting order
     * @throws QueryBuilderException
     */
    public function orderByPrimaryKey(string $mode = 'asc')
    {
        // Get statements
        $statement = $this->statements();

        // check cache for primary key
        if (!isset(self::$tablePrimaryKeys[$this->table])) :

            // @var string $driverNamespace
            $driverNamespace = rtrim($this->getDriverNamespace(), 'Driver');

            // get primary field
            $primary = ClassManager::singleton($driverNamespace . 'Table')->getPrimaryField($this->table);

            $this->orderBy($primary, $mode);

            // cache primary key
            self::$tablePrimaryKeys[$this->table] = $primary;
        
        else:

            // get primary key
            $primary = self::$tablePrimaryKeys[$this->table];

            // run order by
            $this->orderBy($primary, $mode);

        endif;

        // return instance
        return $this;
    }

    /**
     * @method QueryBuilder query
     * @param mixed $loadFrom
     * @param mixed ...$arguments
     * @return mixed
     *
     * Loads a query method from a class or a relationship handler
     * @throws ClassNotFound
     */
    public function query($loadFrom, ...$arguments)
    {
        $classCaller = $this->classUsingLazy;

        switch (gettype($loadFrom)) :
        
            case 'string':

                // build method name
                $method = 'query'.ucwords($loadFrom);

                if ($classCaller === null) :

                    // get table name
                    $tableName = $this->table;

                    // remove prefix
                    $prefix = $this->getPrefix();
                    $tableName = str_replace($prefix, '', $tableName);

                    // remove anything that's not a character
                    $tableName = preg_replace('/[^a-zA-Z]/', ' ', $tableName);

                    $tableName = preg_replace('/(\s*)/', '', $tableName);

                    // build class name
                    $className = '\Relationships\\'.$tableName;

                    // check if class exists
                    if (class_exists($className)) :

                        $classCaller = new $className;

                    endif;

                endif;

            break;

            case 'array':

                // get class name and method.
                list($className, $method) = $loadFrom;
                $method = 'query'.ucwords($method);

                // check class name
                $classCaller = $className; // here we assume $className is an object

                // but let's check if it's a string
                if (is_string($classCaller)) :
                
                    // build singleton
                    $classCaller = \Lightroom\Adapter\ClassManager::singleton($classCaller);

                endif;

            break;

        endswitch;

        // get arguments
        $args = array_splice($arguments, 1);
        array_unshift($args, $this);

        // check if method exists
        if (method_exists($classCaller, $method)) call_user_func_array([$classCaller, $method], $args);

        // return instance
        return $this;
    }

    /**
     * @method QueryBuilder find
     * @param mixed ...$arguments
     * @return mixed
     *
     * Loads a find method from a relationship handler
     * @throws ClassNotFound
     * @throws QueryBuilderException
     * @throws ReflectionException
     */
    public function find(...$arguments)
    {
        // get table name
        $tableName = $this->table;

        // remove prefix
        $prefix = $this->getPrefix();
        $tableName = str_replace($prefix, '', $tableName);

        // remove anything that's not a character
        $tableName = preg_replace('/[^a-zA-Z]/', ' ', $tableName);

        $tableName = preg_replace('/(\s*)/', '', $tableName);

        // build class name
        $className = '\Relationships\\'.$tableName;

        // check if class exists
        if (class_exists($className)) :
        
            // create reflection class
            $ref = new \ReflectionClass($className);
            
            // check argument size
            switch (count($arguments) > 0 && gettype($arguments[0]) == 'string') :
                
                // load a find method
                case true:

                    // get method name
                    list($firstArgs) = $arguments;

                    // build method
                    $method = $className . '::find' . ucwords($firstArgs);

                    // check if method exists
                    if ($ref->hasMethod('find' . ucwords($firstArgs))) :
                    
                        $arguments = array_splice($arguments, 1);
                        array_unshift($arguments, $this);

                        // call method
                        call_user_func_array($method, $arguments);

                    endif;

                break;

                case false:

                    $method = $className . '::find';

                    // load find method
                    if ($ref->hasMethod('find')) :
                    
                        array_unshift($arguments, $this);
                        call_user_func_array($method, $arguments);

                    endif;

                break;

            endswitch;

            // execute query
            if (!$this->pauseExecution) :
                
                return $this->go();

            endif;

        else:

            throw new ClassNotFound($className);

        endif;
        

        // return instance
        return $this;
    }

    // query should fail
    // an intentional request. forces db promise to be returned
    public function queryShouldFail()
    {
        $this->returnPromise = true;

        return $this->go();
    }

    // query should return
    // instead of returning a new promise, return promise from another query
    public function queryShouldReturn($object)
    {
        $this->returnNewQuery = $object;
        return $object;
    }

    // group method
    public function group(\closure $callback)
    {
        $this->pauseExecution = true;

        call_user_func($callback, $this);

        return $this->go();
    }

    /**
     * @method QueryBuilder config
     * @param array $config
     * @return QueryBuilderInterface
     * 
     * This adds a configuration to the query builder.
     */
    public function config(array $config) : QueryBuilderInterface
    {
        foreach ($config as $property => $value) :
        
            if (strtolower($property) == 'allowhtml') $property = 'allowHTMLTags';

            // update QueryBuilder
            $this->{$property} = $value;

        endforeach;

        // return QueryBuilderInterface
        return $this;
    }

    /**
     * @method QueryBuilder primary
     * @param int $primaryId
     * @param mixed $prefix
     *
     * This method appends a primary key to an sql statement
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    public function primary(int $primaryId, $prefix = null)
    {
        // Get statements
        $statement = $this->statements();

        // continue if describe exists
        if (isset($statement['describe'])) :

            if (!isset(self::$tablePrimaryKeys[$this->table])) :

                // get table information
                $describe = $statement['describe'];

                // get query
                $query = is_array($describe) ? $describe['query'] : $describe;

                // replace mask
                $query = strpos($query, '{table}') !== false ? str_replace('{table}', $this->table, $query) : $query . ' ' . $this->table;

                // get query before
                $queryBefore = $this->query;

                // get bind
                $bind = $this->bind;

                // get lastWhere
                $lastWhere = $this->lastWhere;

                // method
                $method = $this->method;

                // run query
                $table = $this->sql($query);

                // update instance
                $this->bind = $bind;
                $this->query = $queryBefore;
                $this->lastWhere = $lastWhere;
                $this->method = $method;

                if ($table->rowCount() > 0) :
                
                    // get primary key
                    $primary = '';

                    // get value
                    $value = (is_array($describe) and isset($describe['value'])) ? $describe['value'] : 'PRI';

                    // get key
                    $key = (is_array($describe) and isset($describe['key'])) ? $describe['key'] : 'Key';

                    // get columnName
                    $columnName = (is_array($describe) and isset($describe['column'])) ? $describe['column'] : 'Field';
                    
                    // get primary key
                    while ($column = $table->fecth(PDO::FETCH_OBJ)) :

                        if ($column->{$key} == $value) :

                            $primary = $column->{$columnName};
                            break;
                        endif;
                    
                    endwhile;

                    // cache primary key
                    self::$tablePrimaryKeys[$this->table] = $primary;

                endif;

                // Close cursor
                $table->closeCursor();

            else:

                // get primary key
                $primary = self::$tablePrimaryKeys[$this->table];

            endif;

            // continue with primary 
            if (isset($primary)) :

                // method
                $method = 'where';

                // use or where
                if ($prefix == 'or') $method = 'orWhere';

                // use and where
                if ($prefix == 'and') $method = 'andWhere';
                
                // add where clause
                if (strlen($primary) > 0) return $this->{$method}($primary . ' = ?', $primaryId);

            endif;

        else:

            throw new QueryBuilderException('there is no default describe statement for your driver.');

        endif;

        // continue with previous build
        return $this;
    }

    // append 'or' primary key to sql statement
    public function orPrimary($key)
    {
        try {
            return $this->primary($key, 'or');
        } catch (QueryBuilderException $e) {
        }
    }

    // append 'and' primary key to sql statement
    public function andPrimary($key)
    {
        try {
            return $this->primary($key, 'and');
        } catch (QueryBuilderException $e) {
        }
    }

    // set bind
    public function setBind(string $key, string $content)
    {
        $this->bind[$key] = $content;

        // return instance
        return $this;
    }

    /**
     * @method QueryBuilder getArguments
     * This method gets the argument passed for a request method/statement
     */
    public function getArguments() : array
    {
        // return array
        return $this->argumentPassed;
    }

    /**
     * @method QueryBuilder setArgument
     * @param string $key
     * @param $value
     * @return QueryBuilder
     *
     * This method sets an argument for a request method
     */
    public function setArgument(string $key, $value)
    {
        // set arguments from argument passed during query build for a statement
        foreach ($this->argumentPassed as $index => $argument) : 
        
            if (is_array($argument)) :
            
                $this->argumentPassed[$index][$key] = $value;
            
            elseif (is_object($argument)) :
            
                $this->argumentPassed[$index]->{$key} = $value;

            endif;

        endforeach;

        // return instance
        return $this;
    }

    /**
     * @method QueryBuilder reBuild
     * @return void
     * This method rebuilds a query for a request method.
     */
    public function reBuild() : void
    {
        $method = $this->method ;

        // reset query
        $this->query = '';

        // reset bind
        $this->bind = [];

        // reset getSql
        $this->getSql = '';

        // reset method
        $this->method = '';

        // call statement
        $this->callMethod($method . 'Statement', $this->argumentPassed);
    }

    /**
     * @method QueryBuilder lastQuery
     * @return mixed
     * This method returns the last successful query that ran
     */
    public function lastQuery()
    {
        return !is_null(self::$lastQueryRan) ? self::$lastQueryRan : $this;
    }

    /**
     * @method QueryBuilder getAllowed
     * @param array $val
     * @param string $sql
     *
     * list of allowed methods for Query Builder
     * @return array
     */
    public function getAllowed(array $val = [null], string &$sql = "") : array
    {
        // house keeping
        if (!isset($val[0])) $val[0] = '';

        // build list of allowed methods
        $this->allowed = $this->loadQueryAllowed($val, $sql);

        // add more allowed queries
        $more = [
            'orLike' => function($val) use ($sql)
            {
                $logic = 'and';

                if (strpos($sql, 'LIKE') !== false) $logic = 'or';
            
                return call_user_func($this->allowed['like']->bindTo($this, static::class), $logic);
            }
        ];

        // set allowed queries
        $this->allowed = array_merge($this->allowed, $more);

        // return array
        return $this->allowed;
    }

    /**
     * @method QueryBuilder resetBuilder
     * @return QueryBuilderInterface This method resets the query builder to it's original state
     *
     * This method resets the query builder to it's original state
     */
    public function resetBuilder() : QueryBuilderInterface
    {
        // create a copy
        $builder = $this;

        // reset
        $builder->pauseExecution = false;
        $builder->argumentPassed = [];
        $builder->cacheQuery = true;
        $builder->allowSaveQuery = true;
        $builder->returnPromise = false;
        $builder->failed = false;
        $builder->getBinds = [];
        $builder->getSql = '';
        $builder->insertKeys = '';
        $builder->allowHTMLTags = false;
        $builder->lastWhere = '';
        $builder->bind = [];
        $builder->method = '';
        $builder->query = '';
        $builder->wherePrefix = ' ';
        $builder->allowed = $this->getAllowed();
        $builder->placeholderReplacement = [];
        $builder->tableAlaise = '';

        // return builder
        return $builder;
    }

    /**
     * @method QueryBuilder getDriverNamespace
     * @return string
     */
    public function getDriverNamespace() : string
    {
        return $this->driverClass;
    }

    /**
     * @method QueryBuilder callMethod
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    private function callMethod(string $method, array $arguments)
    {
        return call_user_func_array([$this, $method], $arguments);
    }

}