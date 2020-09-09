<?php
namespace Lightroom\Database\Drivers\Mysql;

use PDO;
use Exception;
use Lightroom\Database\{
    Helper, QueryBuilderSwitches,
    Configuration, ConnectionSettings as Connection, 
    DatabaseHandler
};
use Lightroom\Database\Interfaces\{
    ConfigurationInterface,
    DatabaseHandlerInterface,
    DriverInterface,
    QueryPromiseInterface,
    QueryBuilderInterface,
    DriverQueryBuilder, 
    DriverQueryInterface,
    TableInterface,
    SchemaInterface
};
use Lightroom\Adapter\ClassManager;
use Lightroom\Exceptions\ClassNotFound;
/**
 * @package Mysql driver
 * @author Amadi ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 * 
 * This package provides access to MYSQL Database system using PDO
 */
class Driver implements DriverInterface, DriverQueryBuilder
{
    // load helper and switches
    use Helper, QueryBuilderSwitches;

    /**
     * @var PDO $pdoInstance
     */
    private $pdoInstance;

    /**
     * @var string $defaultDriver
     */
    private $defaultDriver = '';

    /**
     * @var Driver instance
     */
    private static $driverInstance;

    /**
     * @var string $source
     */
    public static $source = '';

    /**
     * @var array $activeConnections
     */
    private static $activeConnections = [];

    /**
     * @method DriverInterface init
     * @param DatabaseHandlerInterface $handler
     * @return void
     * 
     * This method would be called when driver gets initialized.
     */
    public function init(DatabaseHandlerInterface $handler) : void
    {
        // set instance
        $this->setInstance();
    }

    /**
     * @method DriverInterface connect
     * @param ConfigurationInterface $config
     * @return PDO
     * 
     * Establishes a database connection and returns a PDO object if connection was successful.
     */
    public static function connect(ConfigurationInterface $config) : PDO
    {
        // connect now
        return self::connectWithDefaultPDO($config, 'mysql');
    }

    /**
     * @method Driver __construct
     * Create driver instance
     */
    public function __construct(string $source = '')
    {
        // set instance
        $this->setInstance();

        // check for source, a possible configuration set for default
        if ($source == '') :

            // check for source for this driver
            $drivers = env('database', 'drivers');

            // check now
            if (is_array($drivers)) :

                // run a check for drivers
                foreach ($drivers as $driver => $value) :

                    // get source from configuration
                    if (strtolower($driver) == 'mysql' && $value != null) $source = $value;

                endforeach; 
            endif;

        endif;

        // set source
        self::$source = $source;

        // create connection
        self::createConnection($this);
    }

    /**
     * @method DriverInterface createConnection
     * @param  DriverInterface $driver
     * @return void
     * @throws ClassNotFound
     * @throws Exception
     * Creates a pdo connection
     */
    public static function createConnection(DriverInterface $driver) : void
    {
        // get source
        $source = self::$source;

        // connection target
        $target = $source == '' ? 'default' : $source;

        // get pdo instance from previous connection
        $pdoInstance = isset(self::$activeConnections[$target]) ? self::$activeConnections[$target] : null;

        // check active connection
        if ($pdoInstance === null) :

            // @var DatabaseHandlerInterface $handler 
            $handler = DatabaseHandler::getActiveHandler();

            // create configuration instance
            $configurationClass = ClassManager::singleton(Configuration::class);

            // load configuration
            $configuration = $handler->loadConfiguration($configurationClass, $source);

            // add source to $configuration
            $configuration->setOther([
                'driver.source'  => ($source == '' ? Connection::getDefaultSource() : $source)
            ]);

            // flag connection error for unknown source
            if ($configuration->getOther('driver.source') == '') throw new Exception('Unkown Database Source for Driver '.static::class);

            // get pdo instance
            $pdoInstance = self::connect($configuration);

            // cache connection
            self::$activeConnections[$target] = $pdoInstance;

        endif;

        // set active driver
        $driver->setActiveDriver(Driver::class);

        // create pdo connection
        $driver->setActiveConnection($pdoInstance);
    }


    /**
     * @method Driver getDriverStaticInstance
     * @return DriverInterface
     * 
     * Gets the driver static instance
     */
    public static function getDriverStaticInstance() : DriverInterface
    {
        return self::$driverInstance;
    }

    /**
     * @method Driver getQueryBuilder
     * @return QueryBuilderInterface
     * @throws ClassNotFound
     */
    public function getQueryBuilder() : QueryBuilderInterface
    {
        // return query builder
        return ClassManager::singleton(Builder::class,
        [
            'pdoInstance'   => $this->getActiveConnection(),
            'driverClass'   => $this->getActiveDriver(),
            'driver'        => 'mysql',
            'handler'       => $this,
            'settings'      => $this->getSettings()
        ]);
    }

    /**
     * @method Driver setActiveConnection
     * @param PDO $connection
     * @return void
     * 
     * Saves the active connection in a private property. Would be demanded when required.
     */
    public function setActiveConnection(PDO $connection) : void
    {
        $this->pdoInstance = $connection;
    }

    /**
     * @method DatabaseHandlerInterface setActiveDriver
     * @param string $driver
     * @return void
     * 
     * Saves the active driver for this connection in a private property. Would be demanded when required.
     */
    public function setActiveDriver(string $driver) : void
    {
        $this->defaultDriver = $driver;
    }

    /**
     * @method DatabaseHandlerInterface getActiveConnection
     * @return PDO
     * 
     * Gets the active connection from where saved.
     */
    public function getActiveConnection() : PDO
    {
        return $this->pdoInstance;
    }

    /**
     * @method DatabaseHandlerInterface getActiveDriver
     * @return string
     * 
     * Gets the active driver from where saved.
     */
    public function getActiveDriver() : string
    {
        return $this->defaultDriver;
    }

    /**
     * @method DatabaseHandlerInterface getQuery
     * @return DriverQueryInterface
     * 
     * Returns an instance of driver query
     */
    public function getQuery() : DriverQueryInterface
    {
        // set active connection
        Query::$activeConnections['default'] = [
            'pdoinstance' => $this->getActiveConnection(),
            'prefix'    => self::$configuration['prefix']
        ];

        // return query
        $query = new Query;

        // set the prefix
        $query->prefix = self::$configuration['prefix'];

        // return query
        return $query;
    }

    /**
     * @method DatabaseHandlerInterface getTable
     * @return TableInterface
     * 
     * Returns an instance of driver table
     */
    public function getTable() : TableInterface
    {
        return ClassManager::singleton(Table::class);
    }

    /**
     * @method DatabaseHandlerInterface getSchema
     * @return SchemaInterface
     * 
     * Returns an instance of driver schema
     */
    public function getSchema() : SchemaInterface
    {
        // @var Schema $schema
        $schema = new Schema;

        // set database source
        $schema->databaseSource = self::$configuration['driver.source'];

        // return schema
        return $schema;
    }

    /**
     * @method Driver getSettings
     * @return array
     */
    public function getSettings() : array 
    {
        return self::$configuration;
    }

    /**
     * @method Driver setInstance
     * @return void 
     */
    private function setInstance() : void
    {
        // set instance
        if (self::$driverInstance == null) self::$driverInstance =& $this;
    }
}