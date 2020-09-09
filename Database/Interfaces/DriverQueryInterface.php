<?php
namespace Lightroom\Database\Interfaces;

use PDOStatement;
/**
 * @package Driver Query Interface
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This interface contains all the methods for a driver query class. They return PDOStatements, that can be plugged into a promise and handled diffrently.
 * You should also look at the driver documentation for additional methods.
 */
interface DriverQueryInterface
{
    /**
     * @method DriverQueryInterface select
     * @param mixed ...$arguments 
     * @return PDOStatement
     * 
     * This method makes a select query, and returns a PDOStatement if successful
     */
    public function select(...$arguments) : PDOStatement;
    
    /**
     * @method DriverQueryInterface insert
     * @param mixed ...$arguments 
     * @return PDOStatement
     * 
     * This method makes an insert query, and returns a PDOStatement if successful
     */
    public function insert(...$arguments) : PDOStatement;

    /**
     * @method DriverQueryInterface update
     * @param mixed ...$arguments 
     * @return PDOStatement
     * 
     * This method makes an update query, and returns a PDOStatement if successful
     */
    public function update(...$arguments) : PDOStatement;

    /**
     * @method DriverQueryInterface delete
     * @param mixed ...$arguments
     * @return PDOStatement
     * 
     * This method makes a delete query, and returns a PDOStatement if successful
     */
    public function delete(...$arguments) : PDOStatement;

    /**
     * @method DriverQueryInterface raw_sql
     * @param mixed ...$arguments
     * @return PDOStatement
     * 
     * This method makes a raw query, and returns a PDOStatement if successful
     */
    public function raw_sql(...$arguments) : PDOStatement;

    /**
     * @method DriverQueryInterface setTable
     * @param string $table
     * @return DriverQueryInterface This method sets the current working database table for query
     *
     * This method sets the current working database table for query
     */
    public function setTable(string $table) : DriverQueryInterface;
}