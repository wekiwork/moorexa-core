<?php
namespace Lightroom\Database\Interfaces;

use PDO;
use Lightroom\Database\Interfaces\{
    ConfigurationInterface, DatabaseHandlerInterface
};
/**
 * @package Driver Interface for our database systems
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
interface DriverInterface
{
    /**
     * @method DriverInterface init
     * @param DatabaseHandlerInterface $handler
     * @return void
     * 
     * This method would be called when driver gets initialized.
     */
    public function init(DatabaseHandlerInterface $handler) : void;

    /**
     * @method DriverInterface connect
     * @param ConfigurationInterface $config
     * @return PDO
     * 
     * Establishes a database connection and returns a PDO object if connection was successful.
     * When a switch is requested, a source name would be passed. You should handle for both cases
     */
    public static function connect(ConfigurationInterface $config) : PDO;

    /**
     * @method DriverInterface createConnection
     * @param DriverInterface $driver
     * @return void
     * Creates a pdo connection
     */
    public static function createConnection(DriverInterface $driver) : void;

    /**
     * @method DatabaseHandlerInterface setActiveConnection
     * @param PDO $connection
     * @return void
     * 
     * Saves the active connection in a private property. Would be demanded when required.
     */
    public function setActiveConnection(PDO $connection) : void;

    /**
     * @method DatabaseHandlerInterface setActiveDriver
     * @param string $driver
     * @return void
     * 
     * Saves the active driver for this connection in a private property. Would be demanded when required.
     */
    public function setActiveDriver(string $driver) : void;

    /**
     * @method DatabaseHandlerInterface getActiveConnection
     * @return PDO
     * 
     * Gets the active connection from where saved.
     */
    public function getActiveConnection() : PDO;

    /**
     * @method DatabaseHandlerInterface getActiveDriver
     * @return string
     * 
     * Gets the active driver from where saved.
     */
    public function getActiveDriver() : string;

    /**
     * @method DatabaseHandlerInterface getDriverStaticInstance
     * @return DriverInterface
     * 
     * Gets the driver static instance
     */
    public static function getDriverStaticInstance() : DriverInterface;

    /**
     * @method DatabaseHandlerInterface getQuery
     * @return DriverQueryInterface
     * 
     * Returns an instance of driver query
     */
    public function getQuery() : DriverQueryInterface;

    /**
     * @method DatabaseHandlerInterface getTable
     * @return TableInterface
     * 
     * Returns an instance of driver table
     */
    public function getTable() : TableInterface;

    /**
     * @method DatabaseHandlerInterface getSchema
     * @return SchemaInterface
     * 
     * Returns an instance of driver schema
     */
    public function getSchema() : SchemaInterface;

    /**
     * @method DatabaseHandlerInterface registerChannel
     * @param string $driver
     * @param string $channelClass
     * @return void 
     */
    public static function registerChannel(string $driver, string $channelClass) : void;

    /**
     * @method DatabaseHandlerInterface getChannel
     * @param string $driver
     * @return string 
     */
    public static function getChannel(string $driver = '') : string;
}