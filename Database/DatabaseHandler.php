<?php
namespace Lightroom\Database;

use Lightroom\Database\{
    Interfaces\DatabaseHandlerInterface, Interfaces\DriverInterface, 
    Configuration, ConnectionSettings as Connection
};
use Lightroom\Exceptions\{
    InterfaceNotFound, ClassNotFound
};
use Lightroom\Adapter\ClassManager;
use Exception, PDO, PDOStatement, Closure, ReflectionException;

/**
 * @package Database Handler
 * @author Amadi Ifeanyi <amadiify.com>
 */
class DatabaseHandler
{
    /**
     * @var DatabaseHandler $activeHandler
     * This is just a class name
     */
    public static $activeHandler;

    /**
     * @var DatabaseHandlerInterface $activeHandlerInstance
     * This is the actual class instance
     */
    private static $activeHandlerInstance;

    /**
     * @var array $databaseCreatedFromSource
     */
    private static $databaseCreatedFromSource = [];

    /**
     * @var array $errorCached
     */
    private static $errorCached = [];

    /**
     * @var array $subscribers
     */
    private static $subscribers = [];

    /**
     * @var int $queryUniqueid
     */
    public static $queryUniqueid = 0;

    /**
     * @var array $queriesExecuted
     */
    public static $queriesExecuted = [];

    /**
     * @method DatabaseHandler construct
     * @param string $handler
     * $handler must implements DatabaseHandlerInterface
     *
     * This method will check to see if class exists, check if class implements DatabaseHandlerInterface,
     * then save. Else, an exception would be thrown
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public function __construct(string $handler)
    {
        if (self::$activeHandler === null) :

            // check to know if class exists
            if (!class_exists($handler)) :
                // class not found
                throw new ClassNotFound($handler);
            endif;

            // create reflection class, and verify if class does implement DatabaseHandlerInterface
            $reflection = new \ReflectionClass($handler);

            // Check implementation
            if (!$reflection->implementsInterface(DatabaseHandlerInterface::class)) throw new InterfaceNotFound($handler, DatabaseHandlerInterface::class);

            // save active handler
            self::$activeHandler = $handler;

            // clean up
            unset($reflection, $handler);

            // load database functions
            include_once __DIR__ . '/Functions.php';

            // load state manager
            include_once __DIR__ . '/StateManager.php';

        endif;
    }

    /**
     * @method DatabaseHandler getDefault
     * @return DriverInterface
     * @throws ClassNotFound
     * @throws InterfaceNotFoun 
     * @throws ReflectionException
     */
    public static function getDefault() : DriverInterface
    {
        // check active handler
        if (self::$activeHandler === null) throw new \Exception('No Default database handler registered.');
        
        // get active handler instance
        $active = self::$activeHandlerInstance;

        // is null? means no instance has been created.
        if ($active === null) :

            // create instance 
            $active = ClassManager::singleton(self::$activeHandler);

            // establish connection
            $active = self::createConnection($active);

            // save globally and privately
            self::$activeHandlerInstance = $active;

            // update active
            $active = self::$activeHandlerInstance;

            // save source to connection
            self::$databaseCreatedFromSource[Connection::getDefaultSource()] = $active;

            // clean up
            $reflection = $dbInstance = null;

        endif;

        // return DatabaseHandlerInterface
        return $active;
    }

    /**
     * @method DatabaseHandlerInterface createConnection
     * @param DatabaseHandlerInterface $handler
     *
     * Tries to create a database connection with handler driver
     * @param string $source
     * @return DriverInterface
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public static function createConnection(DatabaseHandlerInterface $handler, string $source = '') : DriverInterface
    {
        // get driver and configuration
        list($driver, $configuration) = self::getDriverAndConfiguration($handler, $source);

        // Connect now
        $pdoInstance = call_user_func([$driver, 'connect'], $configuration);

        // create reflection 
        $reflection = new \ReflectionClass($driver);

        // create driver instance
        $driverInstance = $reflection->newInstanceWithoutConstructor();

        // initialize driver
        $driverInstance->init($handler);

        // save connection
        call_user_func([$driverInstance, 'setActiveConnection'], $pdoInstance);

        // save driver
        call_user_func([$driverInstance, 'setActiveDriver'], $driver);

        // return DriverInterface
        return $driverInstance;
    }

    /**
     * @method DatabaseHandler loadFromSource
     * @param string $source
     *
     * Create connection from database source
     * @return DriverInterface
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public static function loadFromSource(string $source) : DriverInterface
    {
        // not created previously ?
        if (!isset(self::$databaseCreatedFromSource[$source])) :

            // check active handler
            if (self::$activeHandler === null) throw new \Exception('No Default database handler registered.');
            
            // get active handler instance
            $active = self::$activeHandlerInstance;

            // is null? means no instance has been created.
            if ($active === null) :

                // create instance 
                $active = new self::$activeHandler;

            endif;

            // establish connection
            $active = self::createConnection($active, $source);

            // save instance
            self::$databaseCreatedFromSource[$source] = $active;

        else :

            // get instance
            $active = self::$databaseCreatedFromSource[$source];

        endif;

        // return DriverInterface
        return $active;
    }

    /**
     * @method DatabaseHandler getDriverAndConfiguration
     * @param DatabaseHandlerInterface $handler
     * @param string $source
     * @return array
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public static function getDriverAndConfiguration(DatabaseHandlerInterface $handler, string $source = '') : array
    {
        // create configuration instance
        $configurationClass = ClassManager::singleton(Configuration::class);

        // load configuration
        $configuration = $handler->loadConfiguration($configurationClass, $source);

        // add source to $configuration
        $configuration->setOther([
            'driver.source'  => ($source == '' ? Connection::getDefaultSource() : $source)
        ]);

        // get the driver
        $driver = $configuration->getDriver();

        // if driver is not empty then check for class
        if ($driver == '') throw new \Exception('No database driver assigned. Please check your configuration and try again.');

        // check for class 
        if (!class_exists($driver)) throw new ClassNotFound($driver);

        // check if $driver implements DriverInterface
        $reflection = new \ReflectionClass($driver);

        // throw exception if it doesn't implements DriverInterface
        if (!$reflection->implementsInterface(DriverInterface::class)) throw new InterfaceNotFound($driver, DriverInterface::class);

        // return array
        return [$driver, $configuration];
    }

    /**
     * @method DatabaseHandler getDriverSubClass
     * @param string $class
     * @return string
     *
     * This method attempts to find a class from the default driver namespace
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public static function getDriverSubClass(string $class) : string
    {
        // create reflection class
        $reflection = new \ReflectionClass(self::getDefault());

        // get namespace 
        $namespace = $reflection->getNamespaceName();

        // get query class
        $queryClass = $namespace . '\\' . $class;

        // check if class exists
        if (class_exists($queryClass)) :

            // return class
            return $queryClass;

        else:
            // driver sub class not found.
            throw new \Lightroom\Exceptions\ClassNotFound($queryClass);

        endif;
    }

    /**
     * @method DatabaseHandler getActiveHandler
     * @return DatabaseHandlerInterface
     *
     * This method returns the active handler
     * @throws ClassNotFound
     * @throws Exception
     */
    public static function getActiveHandler() : DatabaseHandlerInterface 
    {   
        // throw exception if no active handler has been registered
        if (self::$activeHandler == null) throw new Exception('No Default database handler registered.');

        // return handler
        return ClassManager::singleton(self::$activeHandler);
    }

    /**
     * @method DatabaseHandler loadSubscribers
     * @param PDOStatement $statement
     * @param PDO $pdo
     * @return void
     */
    public static function loadSubscribers(PDOStatement &$statement, PDO &$pdo) : void 
    {
        if (count(self::$subscribers) > 0) :

            // load all
            foreach (self::$subscribers as $subscriber) call_user_func($subscriber, $statement, $pdo);

        else:

            // commit transaction
            if (method_exists($pdo, 'commit')) if ($pdo->inTransaction()) $pdo->commit();

        endif;
    }

    /**
     * @method DatabaseHandler subscribe
     * @param string $subscriber
     * @param Closure $callback
     * @return void
     */
    public static function subscribe(string $subscriber, Closure $callback) : void 
    {
        // load blocked subscribers
        $blockedSubscribers = env('bootstrap', 'database');

        // @var bool $canAdd 
        $canAdd = true;

        // check blocked
        if (\is_array($blockedSubscribers) && isset($blockedSubscribers['blocked_subscribers'])) :

            // flip array
            $keys = array_flip($blockedSubscribers['blocked_subscribers']);

            // check if subscriber has been blocked
            if (isset($keys[$subscriber])) $canAdd = false;

        endif;

        // subscribe closure
        if ($canAdd) self::$subscribers[$subscriber] = $callback;
    }

    /**
     * @method DatabaseHandler errorManager
     * @param \Throwable $exception
     * @return void
     */
    public static function errorManager(\Throwable $exception) : void
    {
        // get the logger
        $logger = logger();

        // debug mode
        $debug = env('bootstrap', 'debug_mode');

        // log error
        if (!\is_null($logger)) :

            // get env
            $environment = defined('DB_TEST_ENV') ? 'TEST' : 'PROD|DEV';

            // get key
            $key = \md5($exception->getFile());

            // log error now
            if (!isset(self::$errorCached[$key])) :

                // echo error
                if ($debug) echo "\n".'You have an error in your Query. Please check your error.log file' . "\n\n";

                // cache
                self::$errorCached[$key] = true;

                // set response code
                http_response_code(424);

            endif;

            // log error
            $logger->error($exception->getMessage(), ['Environment' => $environment, 'Line' => $exception->getLine(), 'File' => $exception->getFile()]);

        else:

            // throw exception
            if ($debug) throw new \Exception($exception->getMessage());

        endif;
    }

    /**
     * @method DatabaseHandler queryRanSuccessfully
     * @param PDOStatement $statement
     * @param string $queryMethod 
     * @return void
     * 
     * This method would be very helpful to us in our test environment.
     */
    public static function queryRanSuccessfully(PDOStatement $statement, string $queryMethod) : void 
    {
        // are we running this query from the test environment ?
        if (defined('TEST_ENVIRONMENT_ENABLED')) : // great!! Happy testing my friend...

            // get unique id
            $uniqueid = self::$queryUniqueid;

            // create dump
            $dump = '';
            
            // we would need to make use of ob_start, ob_get_contents, ob_clean
            ob_start();

            // dump query
            $statement->debugDumpParams();

            // get content
            $dump = ob_get_contents();
            
            ob_end_clean();

            // hash statement
            $dump = md5($dump);

            // do we have a unique id ?
            if ($uniqueid > 0) :

                // push query inside unique box
                self::$queriesExecuted[$uniqueid][$queryMethod][] = [
                    'dump' => $dump,
                    'statement' => $statement
                ];

            else:

                // push query outside unique box
                self::$queriesExecuted[$queryMethod][] = [
                    'dump' => $dump,
                    'statement' => $statement
                ];

            endif;
            

        endif;
    }
}