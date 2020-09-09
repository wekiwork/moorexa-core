<?php
namespace Lightroom\Database\Interfaces;

use Lightroom\Database\QueryBuilder;

interface QueryBuilderInterface
{
    /**
     * @method QueryBuilderInterface loadQueryAllowed
     * @param array $val
     * @param string $sql
     * @return array
     */
    public function loadQueryAllowed(array $val = [null], string &$sql = "") : array;

    /**
     * @method QueryBuilderInterface statements
     * @return array
     * 
     * This loads an array of sql statements for select, update, delete and insert
     * You only need this, if you are using the default query builder.
     */
    public function statements() : array;

    /**
     * @method QueryBuilderInterface getTableName
     * @param string $table
     * @return string
     * 
     * Gets a table name and prepend prefix if registered in the configuration file
     */
    public function getTableName(string $table) : string;

    /**
     * @method QueryBuilderInterface selectStatement
     * @param array $arguments
     * @param string  $statement (like a markup that contains placeholders like '{where}', '{table}')
     * @return QueryBuilder
     */
    public function selectStatement(array $arguments, string $statement);

    /**
     * @method QueryBuilderInterface insertStatement
     * @param array $arguments
     * @param string  $statement (like a markup that contains placeholders like '{values}', '{table}')
     * @return QueryBuilder
     */
    public function insertStatement(array $arguments, string $statement);

    /**
     * @method QueryBuilderInterface deleteStatement
     * @param array $arguments
     * @param string  $statement (like a markup that contains placeholders like '{where}', '{table}')
     * @return QueryBuilder
     */
    public function deleteStatement(array $arguments, string $statement);

    /**
     * @method QueryBuilderInterface updateStatement
     * @param array $arguments
     * @param string  $statement (like a markup that contains placeholders like '{where}', '{table}')
     * @return QueryBuilder
     */
    public function updateStatement(array $arguments, string $statement);

    /**
     * @method QueryBuilderInterface resetBuilder
     * @return QueryBuilderInterface
     * 
     * This method resets the query builder to it's original state
     */
    public function resetBuilder() : QueryBuilderInterface;
}