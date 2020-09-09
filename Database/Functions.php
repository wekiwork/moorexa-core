<?php
namespace Lightroom\Database\Functions;

use Exception;
use Closure, PDOStatement, PDO;
use Lightroom\Database\{
    DatabaseHandler, Promises\QueryPromise,
    Interfaces\DriverQueryInterface
};
use Lightroom\Adapter\ClassManager;
use Lightroom\Database\Resource;
use Lightroom\Database\Interfaces\DriverInterface;
use Lightroom\Exceptions\ClassNotFound;

/**
 * @method DatabaseHandler db() wrapper
 * @param string $table (optional)
 * @return object
 *
 * This returns the default DriverInterface or the default query builder.
 * @throws Exception
 */
function db(string $table = '')
{
    // get default query builder and set the table name
    if ($table !== '') return DatabaseHandler::getDefault()->table($table);

    // return DriverInterface
    return DatabaseHandler::getDefault();
}

/**
 * @method DatabaseHandler db_with() wrapper
 * @param string $source
 * @return DriverInterface
 *
 * This returns the default DriverInterface or the default query builder on a different connection.
 * @throws Exception
 */
function db_with(string $source) : DriverInterface
{
    return DatabaseHandler::loadFromSource($source);
}

/**
 * @method DatabaseHandler map()
 * @param mixed $promise
 * @param mixed $statement
 * @return object (a promise)
 *
 * This function takes a pdo statement and a promise name (optional. Will result to the default promise handler),
 * and returns a promise to provide additional methods and properties for data manipulation.
 */
function map($promise, $statement = null)
{
    // manage switch for promise and statement
    if (is_object($promise)) :

        $statement = $promise;
        $promise = QueryPromise::class;

    endif;

    // load promise
    return call_user_func([$promise, 'loadPromise'], $statement);
    
}

/**
 * @method DatabaseHandler query()
 * @param string $table (optional)
 * @return DriverQueryInterface
 *
 * This function returns an instance of the default driver query class.
 * @throws ClassNotFound
 */
function query(string $table = '') : DriverQueryInterface
{
    // static 
    static $queryStaticClass;

    // check if query class is null
    if ($queryStaticClass === null) :

        // get query class
        $queryStaticClass = DatabaseHandler::getDriverSubClass('Query');

    endif;

    // manage switch
    if ($table === '') return new $queryStaticClass;

    // return with table 
    return call_user_func([$queryStaticClass, 'table'], $table);
}

/**
 * @method DatabaseHandler schema()
 * @param Closure $closure
 * @return void
 *
 * This function creates an instance of the schema class. It binds the schema class into the closure function
 * @throws ClassNotFound
 */
function schema(\Closure $closure) : void
{
    // static 
    static $schemaStaticClass;

    // check if schema class is null
    if ($schemaStaticClass === null) :

        // get schema class
        $schemaStaticClass = DatabaseHandler::getDriverSubClass('Schema');

    endif;

    // create instance
    $schema = new $schemaStaticClass;

    // call closure function
    call_user_func($closure->bindTo($schema, \get_class($schema)));

    // clean up
    unset($schema);
}

/**
 * @method DatabaseHandler table()
 * @param string $method
 * @param mixed ...$arguments
 * @return mixed
 *
 * This function uses the instance of the Table class from the default driver. It takes the method you wish to use as the first argument and mixed arguments
 * @throws ClassNotFound
 */
function table(string $method, ...$arguments)
{
    // static 
    static $tableStaticClass;

    // check if table class is null
    if ($tableStaticClass === null) :

        // get table class
        $tableStaticClass = DatabaseHandler::getDriverSubClass('Table');

    endif;

    // call closure function
    return call_user_func_array([$tableStaticClass, $method], $arguments);
}

/**
 * @method DatabaseHandler rows()
 * @param PDOStatement $statement
 * @return int
 *
 * This function returns rows from queried statement
 */
function rows(PDOStatement $statement) : int
{
    // static 
    static $tableStaticClass;

    // check if table class is null
    if ($tableStaticClass === null) :

        // get table class
        $tableStaticClass = DatabaseHandler::getDriverSubClass('Table');

    endif;

    // call closure function
    return call_user_func([$tableStaticClass, 'getRows'], $statement);
}

/**
 * @method DatabaseHandler driver()
 * @param string $namespace
 * @param array $arguments
 * @return mixed
 * @throws ClassNotFound
 */
function driver(string $namespace, ...$arguments)
{
    // @var string $driver
    $driver = '';

    // @var string $method
    $method = '';

    // get class and method
    $namespace = explode('::', $namespace);

    // update driver
    $driver = $namespace[0];

    // update method
    $method = isset($namespace[1]) ? $namespace[1] : '';

    // check class existance
    $namespace = 'Lightroom\Database\Drivers\\' . ucfirst($driver);

    // check if class exists
    if (!class_exists($namespace)) throw new ClassNotFound($namespace);

    // load static method if requested
    if ($method !== '') return call_user_func_array([$namespace, $method], $arguments);

    // load constructor
    $reflection = new \ReflectionClass($namespace);

    // invoke driver
    $instance = $reflection->newInstanceArgs($arguments);

    // return instance
    return $instance;
}

/**
 * @method DatabaseHandler resource
 * @param mixed $data 
 * @param Closure $callback
 * @return mixed 
 */
function resource($data, Closure $callback)
{
    if (is_array($data) || is_object($data)) :

        // create resource class
        $resource = ClassManager::singleton(Resource::class);

        // load data for array
        if (is_array($data)) $resource->loadData($data);

        // load data for PDOStatement
        if (is_object($data)) :

            // get class
            $getClass = get_class($data);

            // check class
            switch($getClass) :

                // load for pdo statement
                case PDOStatement::class :
                    $resource->loadData($data->fetch(PDO::FETCH_ASSOC), 'object');
                break;

                // maybe query builder
                default:

                    if (method_exists($data, 'row')) :

                        // load array
                        $resource->loadData(func()->toArray($data->row()));

                    elseif (method_exists($data, 'go')) :
                        
                        // load object
                        $data = $data->go();

                        // execute
                        if ($data) :

                            $resource->loadData($data->fetch(PDO::FETCH_ASSOC), 'object');
                            
                        endif;
                       
                    else:
                        // load array
                        $resource->loadData(func()->toArray($data), 'object');

                    endif;

            endswitch;

        endif;

        // load callback
        if ($resource->isLoaded()) $data = $resource->loadCallback($callback);

    endif;

    // return data by default
    return $data;
}