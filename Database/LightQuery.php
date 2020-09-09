<?php
namespace Lightroom\Database;

use PDOStatement, PDO;
use Lightroom\Adapter\ClassManager;
use function Lightroom\Database\Functions\{db, db_with};

/**
 * @package Database LightQuery
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait LightQuery
{
    /**
     * @var array LightQueryConfiguration
     */
    private $LightQueryConfiguration = [];

    /**
     * @var PDOStatement $lastQuery
     */
    private static $lastQuery;

    /**
     * @method LightQuery all
     * @param array $arguments
     * @return PDOStatement
    */
    public static function all(...$arguments) : PDOStatement
    {
       // get table and database
       list($table, $database) = self::___lightQuery();

       // run query
       $query = call_user_func_array([$database->table($table), 'select'], $arguments)->go();

       // set last query
       self::$lastQuery = $query;

       // check for go method
       return $query;
    }

    /**
     * @method LightQuery findBy
     * @param array $arguments
     * @return PDOStatement
    */
    public static function findBy(...$arguments) : PDOStatement
    {
       // get table and database
       list($table, $database) = self::___lightQuery();

       // get the first
       if (isset($arguments[0])) :

        // check if it's an interger
        if (is_integer($arguments[0])) :

            $primary = $database->getTable()->getPrimaryField($table);

            // set primary
            $arguments[0] = $primary . ' = ' . intval($arguments[0]);

        endif;

        // column => value
        if (count($arguments) >= 2) :

            $secondArgument = $arguments[1];

            if (is_string($secondArgument) && count($arguments) == 2) :

                $arguments = [$arguments[0] . ' = ' . $arguments[1]];

            elseif (is_array($secondArgument) && count($arguments) == 2) :

                // get column
                $column = $arguments[0];

                // add values
                $values = [];

                // add value
                foreach ($secondArgument as $value) $values[] = $column . '=' . $value;

                // add or
                $arguments = [implode(' AND ', $values)];
                    
            elseif (is_string($secondArgument) && count($arguments) > 2) :

                // get column
                $column = $arguments[0];

                // add values
                $values = [];

                // get second arguments
                $secondArgument = array_splice($arguments, 1);

                // add value
                foreach ($secondArgument as $value) $values[] = $column . '=' . $value;

                // add or
                $arguments = [implode(' OR ', $values)];

            endif;

        endif;

       endif;

       // run query
       $query = call_user_func_array([$database->table($table), 'select'], $arguments)->go();

       // set last query
       self::$lastQuery = $query;

       // check for go method
       return $query;
    }

    /**
     * @method LightQuery findLike
     * @param array $arguments
     * @return PDOStatement
    */
    public static function findLike(...$arguments) : PDOStatement
    {
       // get table and database
       list($table, $database) = self::___lightQuery();

       // get the first
       if (isset($arguments[0])) :

        // column => value
        if (count($arguments) >= 2) :

            $secondArgument = $arguments[1];

            if (is_string($secondArgument) && count($arguments) == 2) :

                // sanitize value
                if (is_string($arguments[1])) $arguments[1] = filter_var($arguments[1], FILTER_SANITIZE_STRING);

                $arguments = [$arguments[0] . ' LIKE  \'%' . $arguments[1] . '%\''];

            elseif (is_array($secondArgument) && count($arguments) == 2) :

                // get column
                $column = $arguments[0];

                // add values
                $values = [];

                // add value
                foreach ($secondArgument as $value) :

                    // sanitize value
                    if (is_string($value)) $value = filter_var($value, FILTER_SANITIZE_STRING);
                
                    $values[] = $column . ' LIKE \'%' . $value . '%\'';

                endforeach;

                // add or
                $arguments = [implode(' AND ', $values)];
                    
            elseif (is_string($secondArgument) && count($arguments) > 2) :

                // get column
                $column = $arguments[0];

                // add values
                $values = [];

                // get second arguments
                $secondArgument = array_splice($arguments, 1);

                // add value
                foreach ($secondArgument as $value) :

                    // sanitize value
                    if (is_string($value)) $value = filter_var($value, FILTER_SANITIZE_STRING);
                    
                    $values[] = $column . ' LIKE \'%' . $value . '%\'';

                endforeach;

                // add or
                $arguments = [implode(' OR ', $values)];

            endif;

        endif;

       endif;

       // run query
       $query = call_user_func_array([$database->table($table)->select(), 'whereString'], $arguments)->go();

       // set last query
       self::$lastQuery = $query;

       // check for go method
       return $query;
    }

    /**
     * @method LightQuery rows
     * @param array $arguments
     * @return int
    */
    public static function rows(...$arguments) : int
    {
       // check for go method
       return call_user_func_array([static::class, 'all'], $arguments)->rowCount();
    }

    /**
     * @method LightQuery first
     * @param array $arguments
     * @param string $order
     * @return PDOStatement
     */
    public static function first(array $arguments = [], string $order = 'asc') : PDOStatement
    {
       // get table and database
       list($table, $database) = self::___lightQuery();

       // run query
       $query = call_user_func_array([$database->table($table), 'select'], $arguments);

       // return query
       $query = $query->orderBy($database->getTable()->getPrimaryField($table), $order)->limit(0, 1)->go();

       // set last query
       self::$lastQuery = $query;

       // return statement
       return $query;
    }

    /**
     * @method LightQuery last
     * @param array $arguments
     * @param string $order
     * @return PDOStatement
     */
    public static function last(array $arguments = [], string $order = 'desc') : PDOStatement
    {
       // return query
       return call_user_func_array([static::class, 'first'], [$arguments, $order]);
    }

    /**
     * @method LightQuery add
     * @param array $arguments
     * @return int
     */
    public static function add(...$arguments) : int
    {
        // get table and database
        list($table, $database) = self::___lightQuery();

        // find all
        $find = call_user_func_array([static::class, 'all'], $arguments);

        // inserted id
        $insertid = 0;

        // Check if record exists
        if ($find->rowCount() == 0) :

          // add record
          $insert = call_user_func_array([$database->table($table), 'insert'], $arguments)->go();

          // update last insert id
          $find->execute();

        endif;

        $primary = $database->getTable()->getPrimaryField($table);

        // update insert id
        $insertid = $find->fetch(PDO::FETCH_OBJ)->{$primary};

        // set last query
        self::$lastQuery = isset($insert) ? $insert : null;

        // Return int
        return $insertid;
    }

    /**
     * @method LightQuery updateLast
     * @param array $arguments
     * @return bool
     */
    public static function updateLast(...$arguments) : bool
    {
        // get table and database
        list($table, $database) = self::___lightQuery();

        // find last
        $findLast = call_user_func([static::class, 'last']);

        // @var bool $updated
        $updated = false;

        if ($findLast->rowCount() > 0) :

            // add primary
            if (!isset($arguments[1])) :

                // get primary field
                $primary = $database->getTable()->getPrimaryField($table);

                // get primary id
                $primaryid = $findLast->fetch(PDO::FETCH_OBJ)->{$primary};

                // add
                $arguments[1] = $primary . ' = ' . $primaryid;
                
            endif;

            // update table
            if (call_user_func_array([$database->table($table), 'update'], $arguments)->go()->rowCount() > 0) :

                // update was successful
                $updated = true;

            endif;

        endif;

        // return bool
        return $updated;
    }

    /**
     * @method LightQuery update
     * @param array $arguments
     * @return bool
     */
    public static function update(array $data, ...$arguments) : bool
    {
        // get table and database
        list($table, $database) = self::___lightQuery();

        // @var bool $updated
        $updated = false;

        // add primary
        if (is_numeric($arguments[0])) :

            // get primary field
            $primary = $database->getTable()->getPrimaryField($table);

            // add
            $arguments[0] = $primary . ' = ' . $arguments[0];
            
        endif;

        // push data to index zero
        array_unshift($arguments, $data);

        // update table
        if (call_user_func_array([$database->table($table), 'update'], $arguments)->go()->rowCount() > 0) :

            // update was successful
            $updated = true;

        endif;

        // return bool
        return $updated;
    }

    /**
     * @method LightQuery drop
     * @param array $arguments
     * @return bool
     */
    public static function drop(...$arguments) : bool
    {
        // get table and database
        list($table, $database) = self::___lightQuery();

        // @var bool $dropped
        $dropped = false;
        
        if (isset($arguments[0])) :

            // add primary
            if (is_numeric($arguments[0])) :

                // get primary field
                $primary = $database->getTable()->getPrimaryField($table);

                // add
                $arguments[0] = $primary . ' = ' . $arguments[0];
                
            endif;

            // @var closure $closure
            $closure = end($arguments);

            if ($closure !== null && is_callable($closure)) :

                // remove
                $arguments = array_splice($arguments, 0, count($arguments) - 1);

                // run query
                $query = call_user_func_array([$database->table($table), 'select'], $arguments)->go();

                // call closure
                if ($query->rowCount() > 0) call_user_func($closure, $query);

            endif;

            // update table
            if (call_user_func_array([$database->table($table), 'delete'], $arguments)->go()->rowCount() > 0) :

                // deletion was successful
                $dropped = true;

            endif;

        endif;

        // return bool
        return $dropped;
    }

    /**
     * @method LightQuery fromForeign
     * @param array $config
     * @param array $arguments
     * @return PDOStatement
     */
    public static function fromForeign(array $config, ...$arguments) : PDOStatement
    {
        // get table and column
        list($otherTable, $column) = $config;

        // get table and database
        list($table, $database) = self::___lightQuery();

        // get primary field
        if (is_numeric($arguments[0])) $primary = $database->getTable()->getPrimaryField($table);

        // run query
        $query = $database->table($otherTable . ' > fT')->from(
        [
            'cT' => function($builder) use ($table, $column, $arguments, $primary)
            {
                // load primary key
                if (is_numeric($arguments[0])) $arguments[0] = 'mT.' . $primary . ' = ' . $arguments[0];

                // load table
                call_user_func_array([$builder->table($table . ' > mT')->get($column), 'where'], $arguments);
            }

        ])->get()->whereString([ 'fT.' . $column => 'cT.' . $column ])->go();

        // set last query
        self::$lastQuery = $query;

        // return query
        return $query;
    }

    /**
     * @method LightQuery findRow
     * @param array $arguments
     * @return PDOStatement
     */
    public static function findRow(...$arguments) : PDOStatement
    {
        // get table and database
        list($table, $database) = self::___lightQuery();

        // get table column
        $info = $database->getTable()->info($table);

        // @var array columns
        $columns = [];

        if (count($info) > 0) :

            foreach ($info as $column) :

                // get value
                $value = array_values($column);

                // add to column
                $columns[] = $value[0];

            endforeach;

        endif;

        // build where statement
        $where = [];

        // run arguments
        foreach ($arguments as $value) :

            $argumentWhere = [' ('];

            // @var array $statement
            $statement = [];

            // run columns
            foreach ($columns as $column) :

                // sanitize string
                if (is_string($value)) $value = filter_var($value, FILTER_SANITIZE_STRING);

                // add statement
                $statement[] = $column . ' LIKE \'%'.$value.'%\' ';

            endforeach;

            // add statement
            $argumentWhere[] = implode(' OR ', $statement);

            // close group
            $argumentWhere[] = ') ';

            // Add to where
            $where[] = implode('', $argumentWhere);

        endforeach;

        // where joined
        $where = implode(' OR ', $where);

        // add to query
        $query = call_user_func_array([$database->table($table)->get(), 'whereString'], [$where])->go();

        // set last query
        self::$lastQuery = $query;

        // return statement
        return $query;
    }

    /**
     * @method LightQuery setTable
     * @param string $table
     * @return void
     */
    public static function setTable(string $table = '') : void 
    {
        // default to static class
        $table = $table == '' ? strtolower(basename(str_replace('\\', '/', static::class))) : $table;

        // set in config
        self::___setConfig('table', $table);
    }

    /**
     * @method LightQuery setDatabase
     * @param string $databaseSource
     * @return void
     */
    public static function setDatabase(string $databaseSource) : void
    {
        // set database
        self::___setConfig('database', db_with($databaseSource));
    }

    /**
     * @method LightQuery lastQuery
     * @return PDOStatement
     */
    public static function lastQuery() : PDOStatement
    {
        return self::$lastQuery;
    }

    /**
     * @method LightQuery prepareLightQuery
     * @return void
     */
    private static function prepareLightQuery() : void
    {
        // get instance
        $self = self::___instance();

        // get table name
        $table = property_exists($self, 'table') ? $self->table : '';

        // add table
        if (!isset($self->LightQueryConfiguration['table'])) self::setTable($table);

        // add database
        if (!isset($self->LightQueryConfiguration['database'])) self::___setConfig('database', db());
    }

    /**
     * @method LightQuery ___setConfig
     * @param string $name
     * @param mixed $value
     * @return void
     */
    private static function ___setConfig(string $name, $value) : void 
    {
        // get instance
        $self = self::___instance();

        // set data
        $self->LightQueryConfiguration[$name] = $value;
    }

    /**
     * @method LightQuery ___getConfig
     * @param string $name
     * @return mixed
     */
    private static function ___getConfig(string $name) 
    {
        // get instance
        $self = self::___instance();

        // return config data
        return isset($self->LightQueryConfiguration[$name]) ? $self->LightQueryConfiguration[$name] : null;
    }

    /**
     * @method LightQuery ___instance
     * @return mixed
     * return instance of static class
     */
    private static function ___instance() 
    {
        return ClassManager::singleton(static::class);
    }

    /**
     * @method LightQuery ___lightQuery
     * @return array
     */
    private static function ___lightQuery() : array 
    {
        // prepare light query
        self::prepareLightQuery();

        // return query
        return [self::___getConfig('table'), self::___getConfig('database')]; 
    } 
}