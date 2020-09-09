<?php
namespace Lightroom\Database\Interfaces;

use Closure;
/**
 * @package Driver Schema Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface SchemaInterface
{
    /**
     * @method SchemaInterface getDataTypes
     * @return array
     * 
     * This method returns all supported data types for a driver
     */
    public static function getDataTypes() : array;

    /**
     * @method SchemaInterface getTableName
     * @return string
     * 
     * This method returns a proper formatted table name for a driver
     */
    public function getTableName() : string;

    /**
     * @method SchemaInterface sql
     * @param Closure $callback
     * @return void
     * 
     * This method takes a closure function and runs a query on the return value of that closure
     */
    public function sql(Closure $callback) : void;

    /**
     * @method SchemaInterface promise
     * @param Closure $callback
     * @return void
     * 
     * This method takes a closure and feeds it with progress report during migration process
     */
    public function promise(Closure $callback) : void;

    /**
     * @method SchemaInterface addJobAndSave
     * @param array $jobs
     * @param string $newSql
     * @return void
     * 
     * This method takes a list of sql jobs and saves it to a file
     */
    public function addJobAndSave(array $jobs, string $newSql) : void;

    /**
     * @method SchemaInterface drop
     * @param Closure $callback
     * @return void
     * 
     * This method prepares an sql statement to drop the current table
     */
    public function drop(Closure $callback) : void;

    /**
     * @method SchemaInterface options
     * @param Closure $callback
     * @return void
     * 
     * This method prepares an sql statement to alter a table with options
     */
    public function options(Closure $callback) : void;

    /**
     * @method SchemaInterface rename
     * @param string $newName
     * @return void
     * 
     * This method attempts to rename a table with a new name
     */
    public function rename(string $newName) : void;

    /**
     * @method SchemaInterface engine
     * @param string $engineName
     * @return void
     * 
     * This method attempts to change the engine of the current table
     */
    public function engine(string $engineName) : void;

    /**
     * @method SchemaInterface collation
     * @param string $collation
     * @return void
     * 
     * This method attempts to change the character set and the default collation of the current table
     */
    public function collation(string $collation) : void;

    /**
     * @method SchemaInterface __call
     * @param string $method
     * @param array $arguments
     * @return SchemaInterface
     * 
     * This method provides a system for building columns for the current table
     */
    public function __call(string $method, array $arguments) : SchemaInterface;

    /**
     * @method SchemaInterface createStatement
     * @param string $statement
     * @return void
     * 
     * This method attempts to run a create sql statement for the current table
     */
    public function createStatement(string $statement) : void;

    /**
     * @method SchemaInterface alterStatement
     * @param string $statement
     * @return void
     * 
     * This method attempts to run an alter statement for the current table
     */
    public function alterStatement(string $statement) : void;

    /**
     * @method SchemaInterface insertStatement
     * @param string $statement
     * @return void
     * 
     * This method attempts to run an insert sql statement for the current table
     */
    public function insertStatement(string $statement) : void;

    /**
     * @method SchemaInterface updateStatement
     * @param string $statement
     * @return void
     * 
     * This method attempts to run an update sql statement for the current table
     */
    public function updateStatement(string $statement) : void;

    /**
     * @method SchemaInterface deleteStatement
     * @param string $statement
     * @return void
     * 
     * This method attempts to run a delete sql statement for the current table
     */
    public function deleteStatement(string $statement) : void;

    /**
     * @method SchemaInterface saveSchema
     * @return SchemaInterface
     * 
     * This method saves a schema to a file. It can also check what changed and write to the list of jobs to be ran for the current table
     */
    public function saveSchema() : SchemaInterface;

    /**
     * @method SchemaInterface dropSchema
     * @return SchemaInterface
     * 
     * This method saves a drop statement to a file and also writes to the list of jobs for the current table
     */
    public function dropSchema() : SchemaInterface;

    /**
     * @method SchemaInterface getSqlSavePath
     * @return string
     * 
     * This method returns a working path to save generated sql statements.
     */
    public function getSqlSavePath() : string;
}