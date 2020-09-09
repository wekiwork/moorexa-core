<?php
namespace Lightroom\Database\Drivers\Mysql;

use Lightroom\Database\{
    DatabaseHandler as Handler, QueryBuilderMagicMethods, 
    TablePrefixing, Drivers\DriversHelper, 
    Interfaces\DriverQueryInterface, Cache\FileQueryCache
};
use Lightroom\Exceptions\{
    ClassNotFound, InterfaceNotFound
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
use PDO, PDOStatement, ReflectionException, Exception;

/**
 * @package Mysql Query class
 * @author Amadi Ifeanyi <amadiify,com> 
 */
class Query implements DriverQueryInterface
{
    // use table prefixing and drivers helper
    use TablePrefixing, DriversHelper, FileQueryCache;

    /**
     * @var array $activeConnections
     */
    public static $activeConnections = [];
    
    /**
     * @var string $table
     */
    private $table = '';

    /**
     * @var string $source
     */
    public $source = '';

    /**
     * @var Query $instance
     */
    private static $instance;

    /**
     * @method DriverQueryInterface setTable
     * @param string $table
     * @return DriverQueryInterface
     *
     * This method sets the current working database table for query
     */
    public function setTable(string $table) : DriverQueryInterface
    {
        // set table 
        $this->table = $table;

        // return instance
        return $this;
    }

    /**
     * @method Query __callStatic
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        // return switch
        return self::getInstance()->requestSwitch($method, $arguments);
    }

    /**
     * @method Query __call
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        // return switch
        return $this->requestSwitch($method, $arguments);
    }

    /**
     * @method DriverQueryInterface select
     * @param mixed ...$arguments
     * @return PDOStatement
     *
     * This method makes a select query, and returns a PDOStatement if successful
     * @throws Exception
     */
    public function select(...$arguments) : PDOStatement
    {
        // get statement builder
        $builder = new class()
        {
            // @var string $query
            public $query = '';

            // @var array $bind
            public $bind = [];

            // get bind methods and queries helper
            use selectStatement, QueryBuilderMagicMethods, QueriesHelper;
        };

        // execute query
        $result = $this->getResult('select', $builder, $arguments);

        // clean up
        $builder = null;

        // return statement
        return $result;
    }

    /**
     * @method DriverQueryInterface insert
     * @param mixed ...$arguments
     * @return PDOStatement
     *
     * This method makes an insert query, and returns a PDOStatement if successful
     * @throws Exception
     */
    public function insert(...$arguments) : PDOStatement
    {
       // get statement builder
       $builder = new class()
       {
           // @var string $query
           public $query = '';

           // @var array $bind
           public $bind = [];

           // get bind methods and queries helper
           use insertStatement, QueryBuilderMagicMethods, QueriesHelper;
       };

       // execute query
       $result = $this->getResult('insert', $builder, $arguments);

       // clean up
       $builder = null;

       // return statement
       return $result;
    }

    /**
     * @method DriverQueryInterface update
     * @param mixed ...$arguments
     * @return PDOStatement
     *
     * This method makes an update query, and returns a PDOStatement if successful
     * @throws Exception
     */
    public function update(...$arguments) : PDOStatement
    {
       // get statement builder
       $builder = new class()
       {
           // @var string $query
           public $query = '';

           // @var array $bind
           public $bind = [];

           // get bind methods and queries helper
           use updateStatement, QueryBuilderMagicMethods, QueriesHelper;
       };

       // execute query
       $result = $this->getResult('update', $builder, $arguments);

       // clean up
       $builder = null;

       // return statement
       return $result;
    }

    /**
     * @method DriverQueryInterface delete
     * @param mixed ...$arguments
     * @return PDOStatement
     *
     * This method makes a delete query, and returns a PDOStatement if successful
     * @throws Exception
     */
    public function delete(...$arguments) : PDOStatement
    {
       // get statement builder
       $builder = new class()
       {
           // @var string $query
           public $query = '';

           // @var array $bind
           public $bind = [];

           // get bind methods and queries helper
           use deleteStatement, QueryBuilderMagicMethods, QueriesHelper;
       };

       // execute query
       $result = $this->getResult('delete', $builder, $arguments);

       // clean up
       $builder = null;

       // return statement
       return $result;
    }

    /**
     * @method DriverQueryInterface raw_sql
     * @param mixed ...$arguments
     * @return PDOStatement
     *
     * This method makes a raw query, and returns a PDOStatement if successful
     * @throws Exception
     */
    public function raw_sql(...$arguments) : PDOStatement
    {
       // get statement builder
       $builder = new class()
       {
           // @var string $query
           public $query = '';

           // @var array $bind
           public $bind = [];

           // get bind methods and queries helper
           use sqlStatement, QueryBuilderMagicMethods, QueriesHelper;
       };

       // execute query
       $result = $this->getResult('sql', $builder, $arguments);

       // clean up
       $builder = null;

       // return statement
       return $result;
    }

    /**
     * @method Query requestSwitch
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function requestSwitch(string $method, array $arguments)
    {
        // get method
        switch($method) :

            // table
            case 'table':
               return $this->setTable($arguments[0]);
            break;

            // with
            case 'with':
                $this->source = $arguments[0];
            break;

            case 'sql':
                return call_user_func_array([$this, 'raw_sql'], $arguments);

        endswitch;

        // return instance
        return $this;
    }

    /**
     * @method Query getPdoInstance
     * @param string $source
     * @return PDO
     * Create a pdo instance
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public function getPdoInstance(string $source = '') : PDO
    {
        // connection name
        $connectionName = $source == '' ? 'default' : $source;

        // get active connection if made.
        if ($connectionName != '' && isset(self::$activeConnections[$connectionName])) :
            
            // @var array $connection
            $connection = self::$activeConnections[$connectionName];

            // get prefix
            $this->prefix = $connection['prefix'];

            // return pdo instance
            return $connection['pdoinstance'];

        endif;

        // get driver instance
        $driver = new Driver($source);

        // Connect now
        $pdoInstance = $driver->getActiveConnection();

        // get configuration
        $configuration = $driver->getSettings();

        // set the prefix
        $this->prefix = $configuration['prefix'];

        // set driver source
        $this->driverSource = $configuration['driver.source'];

        // save instance
        self::$activeConnections[$connectionName] = ['pdoinstance' => $pdoInstance, 'prefix' => $this->prefix];

        // return PDO instance
        return $pdoInstance;
    }

    /**
     * @method Query getResult
     * @param string $queryMethod
     * @param mixed $builder
     * @param array $arguments
     * @return PDOStatement
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    private function getResult(string $queryMethod, $builder, array $arguments) : PDOStatement
    {
        // build statements
        $statements = [
            'update' => 'UPDATE {table} SET {query} {where}',
            'insert' => 'INSERT INTO {table} ({column}) VALUES {query}',
            'delete' => 'DELETE FROM {table} {where}',
            'select' => 'SELECT {column} FROM {table} {where}',
            'sql'    => isset($arguments[0]) ? $arguments[0] : '',
        ];

        // continue with query statement
        if (isset($statements[$queryMethod])) :

            // get statement
            $statement = $statements[$queryMethod];

            // get pdo instance
            $pdo = $this->getPdoInstance($this->source);

            if ($this->table != '') :
                // replace {table} with table name
                $statement = str_replace('{table}', $this->getTableName($this->table), $statement);
            endif;

            // @var array $parameters
            $parameters = $queryMethod != 'sql' ? [$arguments, $statement] : $arguments;

            // run statement
            call_user_func_array([$builder, $queryMethod . 'Statement'], $parameters);

            // listening events
            self::queryListenerFor(Driver::class, $queryMethod, $this->table, $builder->query, $builder->bind);

            // prepare binds
            list($builder->bind, $builder->query) = $this->prepareBinds($builder->bind, $queryMethod, $builder->query);

            // prepare query
            $smt = $this->prepare($builder->query, $pdo, $builder->bind);

            // execute query
            $result = $this->execute($smt, $pdo);

            // clean up
            $smt = $pdo = null;

            // return statement
            return $result;

        else:
            throw new Exception('Statement not found for query method "'.$queryMethod.'"');
        endif;
    }

    /**
     * @method Query getInstance
     * @return Query
     * 
     * Gets the instance of this class
     */
    private static function getInstance() : Query
    {
        if (self::$instance == null) :

            // create instance
            self::$instance = new self;

        endif;

        // return Query
        return self::$instance;
    }
}