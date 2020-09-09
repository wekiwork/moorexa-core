<?php
namespace Lightroom\Database\Drivers;

use PDO, PDOStatement;
use Lightroom\Adapter\ClassManager;
/**
 * @package Table Helper
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This package contains all the required methods for a table class. Utilizing class must implements TableInterface 
 * and TableHelperInterface
 */
trait TableHelper
{
    /**
     * @method TableInterface exists
     * @param string $table
     * @return bool
     * 
     * Checks if a table exists
     */
	public static function exists(string $table) : bool
	{   
        // @var bool $exists
        $exists = false;

		// get query class
        $database = self::getQueryInstance();

        // get tables
        $tables = $database->sql(self::existsStatement());

        foreach ($tables->fetchAll() as $index => $row) :
            
            if (strcmp($row[0], $database->getTableName($table)) === 0) :
            
                $exists = true;
                break;

            endif;

        endforeach;
	
		// return bool
		return $exists;
    }

    /**
     * @method TableInterface drop
     * @param string $tableName
     * @param closure $callback
     * @return bool
     * 
     * Drops a table
     */ 
	public static function drop(string $tableName, $callback=null) : bool
	{
        // @var bool $dropped
        $dropped = false;

        // check if table exists
		if (self::exists($tableName)) :

            // get the table name
			$tableName = self::getQueryInstance()->getTableName($tableName);

			// get schema
			$schema = self::getSchemaInstance();
			$schema->tableName = $tableName;
			$schema->dropTables[$tableName] = true;

            // drop closure
			$drop = function($drop, $records)
			{
				// drop table
				$drop();
			};

            // push command to $callback if found
            if (is_callable($callback)) $drop = $callback;

			// drop table
            $schema->drop($drop);
            
			if (count($schema->sqljob) > 0) :

                // get all sql's from jobs
				foreach ($schema->sqljob as $sql) :
                    
                    // try run query
					if (self::$queryInstance->sql($sql)) $dropped = true;
                    
				endforeach;
                
            endif;

            // clean up
            unset($schema, $drop);
            
		endif;

        // return bool
		return $dropped;
    }
    
    /**
     * @method TableInterface info
     * @param string $table
     * @param closure $callback
     * @return mixed
     */
	public static function info(string $table, \Closure $callback = null) 
	{
        // check if table exists
        if (self::exists($table)) :
		
            // get table with prefix
			$table = self::getQueryInstance()->getTableName($table);
            
            // get all fields
            $statement = self::getQueryInstance()->sql(self::infoStatement($table));

			if (self::getRows($statement) > 0) :
            
                // using value
                $usingValue = function($value) use (&$statement)
                {
                    // @var array $found
                    $found = [];

                    while($row = $statement->fetch(PDO::FETCH_ASSOC)) :
                    
                        // update found
                        if (in_array($value, array_values($row))) $found[] = (object) $row;
                        
                    endwhile;
                    
                    // return found
                    return $found;
                };

                // @var closure $usingFields
                $usingFields = function() use (&$statement)
                {
                    // @var array $structure
                    $structure = [];

                    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) $structure[] = $row;

                    return $structure;
                };

                // @var closure $closureForCallback
                $closureForCallback = function($value = null) use (&$usingValue, &$usingFields)
                {
                    if ($value !== null) :

                        // use value closure
                        return call_user_func($usingValue, $value);

                    endif;

                    // return fields
                    return call_user_func($usingFields);
                };

                // use callback if not null
                if ($callback !== null && is_callable($callback)) :

                    // call callback function
                    return call_user_func($callback, $closureForCallback);

                else:

                    // return $usingFields
                    return call_user_func($usingFields);

                endif;
	
            endif;

            // return bool
            return false;

        endif;
        
        // return string
		return "Table doesn't exists";
    }
    
    /**
     * @method TableInterface create
     * @param string $tablename
     * @param Closure $callback
     * @return bool
     * 
     * This method creates a table
     */
    public static function create(string $tablename, \Closure $callback) : bool
	{
		if (is_callable($callback))
		{
            // continue only if table doesn't exists
            // !self::exists($tablename)
            if (true) :
                
                // @var string $tablename
                $tablename = self::getQueryInstance()->getTableName($tablename);

                $schema = self::getSchemaInstance();
                $schema->tableName = $tablename;

                // get database
                $database = self::getQueryInstance();

                // get table schema
                call_user_func($callback, $schema);

                // save schema to file 
                if (count($schema->buildQuery) > 0 || $schema->sqlString != "") $schema->saveSchema();

                // get total jobs
                $total = count($schema->sqljob);
                $rows = 0;
                $savePath = $schema->getSqlSavePath();

                // @var int $now
                $now = 0;

                // continue with jobs
                if ($total > 0) :
                
                    foreach ($schema->sqljob as $sql) :
                    
                        if (strlen($sql) > 4) :
                        
                            try
                            {
                                // execute statement
                                $statement = $database->sql($sql);

                                // update rows
                                $rows += $statement->rowCount();

                                // increment by one
                                if ($statement) $now++;
                                
                            }
                            catch(\Exception $e)
                            {
                                
                                // roll back
                                $content = trim(file_get_contents($savePath));
                                $ending = strrpos($content, $sql . ";");

                                $length = strlen($sql . ";");
                                $content = substr_replace($content, '', $ending, $length+1);
                                file_put_contents($savePath, $content);

                                // throw exception
                                throw new \Exception($e->getMessage());
                            }

                        endif;

                    endforeach;

                endif;

                if (isset($schema->promises[$tablename])) :
                
                    $promise = $schema->promises[$tablename];
                    $callback = $promise[0];
                    $promise = $promise[1];
                    $promise->table = $tablename;

                    call_user_func($callback, 'complete', $promise);

                endif;

                // check execution
                return $now > 0 ? true : false;

            endif;

            return true;
		}

		return false;
    }   

    /**
     * @method TableInterface getRows
     * @param PDOStatement $statement
     * @return int
     */
    public static function getRows(PDOStatement $statement) : int
    {
        // @var int $rows
        $rows = $statement->rowCount();

        // try fetch column
        if ($rows == 0) $rows = (int) $statement->fetchColumn();

        // return rows
        return $rows;
    }
}