<?php
namespace Lightroom\Database;

/**
 * @package QueryBuilder Properties
 * Default properties for queryBuilder trait
 */
trait QueryBuilderProperties
{
    // pack all errors occur.
    private $errorPack = [];

    // get database driver from database handler
    private $driver;

    // sql query
    public $query = '';

    // set query method
    public $method = '';

    // pdo bind values
    public $bind = [];

    // last where statement
    private $lastWhere = '';

    // database.table
    public $table = '';

    // pdo instance
    public $pdoInstance = null;

    // allow html tags
    private $allowHTMLTags = false;

    // insert keys
    private $insertKeys = '';

    // get query
    public $getSql = '';

    // get binds
    public $getBinds = [];

    // allow query called
    private $allowedQueryCalled = false;

    // class using lazy method
    public $classUsingLazy;

    // add wherePrefix
    private $wherePrefix = ' ';

    // insert data
    private $argumentPassed = [];

    // last successful query ran
    public static $lastQueryRan = null;

    // bind externals
    public static $bindExternal = [];

    // pause query execution
    public $pauseExecution = false;

    // primary keys cached
    private static $tablePrimaryKeys = [];

    // table name aliase
    private $tableAlaise = '';

    // placeholder replacement
    private $placeholderReplacement = [];

    // get driver class
    protected $driverClass = '';
}