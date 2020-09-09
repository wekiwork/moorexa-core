<?php
namespace Lightroom\Database\Interfaces;

use Closure, PDOStatement;
use Lightroom\Database\Interfaces\{
    DriverQueryInterface, SchemaInterface
};
/**
 * @package Driver Table Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface TableInterface
{
    /**
     * @method TableInterface getQueryInstance
     * @return DriverQueryInterface
     */
    public static function getQueryInstance() : DriverQueryInterface;

    /**
     * @method TableInterface getSchemaInstance
     * @return SchemaInterface
     */
    public static function getSchemaInstance() : SchemaInterface;

    /**
     * @method TableInterface exists
     * @param string $table
     * @return bool
     * 
     * Checks if a table exists
     */
    public static function exists(string $table) : bool;
    
    /**
     * @method TableInterface drop
     * @param string $tableName
     * @param closure $callback
     * @return bool
     * 
     * Drops a table
     */ 
    public static function drop(string $tableName, $callback=null) : bool;
    
    /**
     * @method TableInterface info
     * @param string $table
     * @param closure $callback
     * @return mixed
     */
    public static function info(string $table, \Closure $callback = null);
    
    /**
     * @method TableInterface create
     * @param string $tableName
     * @param Closure $callback
     * @return bool
     * 
     * This method creates a table
     */
    public static function create(string $tableName, \Closure $callback) : bool;

    /**
     * @method TableInterface getRows
     * @param PDOStatement $statement
     * @return int
     */
    public static function getRows(PDOStatement $statement) : int;

    /**
     * @method TableInterface getPrimaryField
     * @param string $table
     * @return string 
     */
    public function getPrimaryField(string $table) : string;
    
}