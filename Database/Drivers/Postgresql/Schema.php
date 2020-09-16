<?php
namespace Lightroom\Database\Drivers\Postgresql;

use Closure;
use Lightroom\{Core\FunctionWrapper, Common\File, Adapter\ClassManager, Exceptions\ClassNotFound};
use Lightroom\Database\{
    Interfaces\SchemaInterface, Drivers\SchemaHelper,
    Drivers\SchemaProperties, Interfaces\SchemaHelperInterface
};
use ReflectionException;

/**
 * @package Postgresql Schema class
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is the default Schema class for Postgresql database system
 */
class Schema implements SchemaInterface, SchemaHelperInterface
{
    // use schema helper 
    use SchemaProperties, SchemaHelper;

    /**
     * @var string $_datatypes
     */
    public static $_datatypes = 'money,varbit,bit,smallserial,serial,bigserial,numeric,double,real,bool,boolean,timestamp,date,time';

    /**
     * @method SchemaInterface drop
     * @param Closure $callback
     * @return void
     *
     * This method prepares an sql statement to drop the current table
     * @throws ReflectionException
     */
    public function drop(Closure $callback) : void
    {
        if (isset($this->dropTables[$this->tableName])) :
        
            // @var array $parameters
			$parameters = [];
            
            // closure function
			$drop = function(){
				$table = $this->tableName;
				$this->sql = "DROP TABLE `$table`";
				$this->sqljob[] = $this->sql;
				$this->dropSchema();
				$this->sql = '';
			};

            $database = new Query;

            // set source
            $database->source = $this->databaseSource;

            // get pdo statement
            $table = $database->table($this->tableName)->select();
            
            // get parameters
			FunctionWrapper::getParameters($callback, $parameters, [$drop, $table]);

            // call closure function
            call_user_func_array($callback, $parameters);
            
		endif;
    }

    /**
     * @method SchemaInterface promise
     * @param Closure $callback
     * @return void
     * 
     * This method takes a closure and feeds it with progress report during migration process
     */
	public function promise(Closure $callback) : void
	{
		if (is_callable($callback)) :
        
            // get query instance
            $database = new Query;

            // set source
            $database->source = $this->databaseSource;

            // set table
			$database->setTable($this->tableName);

            // stack to promises
			$this->promises[$this->tableName] = [$callback, $database];
            
            // @var string $status
			$status = 'pending';

            // call closure function
            call_user_func($callback, $status, $database);
            
		endif;
    }

    /**
     * @method SchemaHelperInterface __rename
     * @param string $table
     * @param string $newName
     * @return string
     * @throws ClassNotFound
     */
    public function __rename(string $table, string &$newName) : string 
    {
        // @var string $newName
        $newName = ClassManager::singleton(Query::class)->getTableName($newName);

        // return string
        return "RENAME TABLE `{$table}` TO `{$newName}`";
    }
    
    /**
     * @method SchemaHelperInterface __engine
     * @param string $table
     * @param string $engine
     * @return string
     */
    public function __engine(string $table, string $engine) : string 
    {
        return "ALTER TABLE `{$table}` ENGINE = {$engine}";
    }

    /**
     * @method SchemaHelperInterface __collation
     * @param string $table
     * @param string $charset
     * @param string $collation
     * @return string
     */
    public function __collation(string $table, string $charset, string $collation) : string 
    {
        return "ALTER TABLE `{$table}` DEFAULT CHARSET={$charset} COLLATE {$collation}";
    }

    /**
     * @method SchemaHelperInterface __createStatement
     * @return string
     */
    public function __createStatement() : string 
    {
        return "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (";
    }

    /**
     * @method SchemaInterface __call
     * @param string $method
     * @param array $arguments
     * @return SchemaInterface
     * 
     * This method provides a system for building columns for the current table
     */
    public function __call(string $method, array $arguments) : SchemaInterface
    {   
        // set datatypes
        self::$datatypes = self::$_datatypes . ',' . self::$datatypes;

        // use build table schema method
        $schema = $this->buildTableSchema($method, $arguments);

        if (is_array($schema)) :

            // get method, column, length and other 
            list($method, $column, $length, $other) = $schema;

        endif;
		
		return $this;
    }

    /**
     * @method SchemaHelperInterface __current
     * @return string
     */
    public function __current() : string
    {
        return ' default CURRENT_TIMESTAMP, ';
    }

    /**
     * @method SchemaHelperInterface __increment
     * @param string $method
     * @param mixed $length
     * @param mixed $other
     * @return void
     */
    public function __increment(string &$method, &$length, &$other) : void
    {
        $method = 'bigint';

        if ($length == '') $length = 20;
        
        $other = 'auto_increment primary key';
    }

    /**
     * @method SchemaHelperInterface __unique
     * @param string $column
     * @return void
     */
    public function __unique(string $column) : void
    {
        $keys = array_keys($this->queryInfo);
        $end = end($keys);
        $column = $end;
        $string = "\t". 'UNIQUE(`'.$column.'`), ';
        $this->buildQuery[] = $string;
    }

    /**
     * @method SchemaInterface createStatement
     * @param string $statement
     * @return void
     * 
     * This method attempts to run a create sql statement for the current table
     */
    public function createStatement(string $statement) : void
    {
        $this->runCommandStatement('CREATE TABLE IF NOT EXISTS', $statement);
    }

    /**
     * @method SchemaInterface alterStatement
     * @param string $statement
     * @return void
     * 
     * This method attempts to run an alter statement for the current table
     */
    public function alterStatement(string $statement) : void
    {
        $this->runCommandStatement('ALTER TABLE', $statement);
    }

    /**
     * @method SchemaInterface insertStatement
     * @param string $statement
     * @return void
     * 
     * This method attempts to run an insert sql statement for the current table
     */
    public function insertStatement(string $statement) : void
    {
        $this->runCommandStatement('INSERT INTO', $statement);
    }

    /**
     * @method SchemaInterface updateStatement
     * @param string $statement
     * @return void
     * 
     * This method attempts to run an update sql statement for the current table
     */
    public function updateStatement(string $statement) : void
    {
        $this->runCommandStatement('UPDATE', $statement);
    }

    /**
     * @method SchemaInterface deleteStatement
     * @param string $statement
     * @return void
     * 
     * This method attempts to run a delete sql statement for the current table
     */
    public function deleteStatement(string $statement) : void
    {
        $this->runCommandStatement('DELETE FROM', $statement);
    }

    /**
     * @method SchemaInterface getSqlSavePath
     * @return string
     *
     * This method returns a working path to save generated sql statements.
     * @throws ClassNotFound
     */
    public function getSqlSavePath() : string
    {
        // get query instance
        $database = ClassManager::singleton(Query::class);

        // get pdo instance 
        $database->getPdoInstance($this->databaseSource);

        // set source
        $source = $this->databaseSource == '' ? $database->driverSource : $this->databaseSource;

        // return path
        return get_path(func()->const('database'), '/Sql/schema_postgresql_'.md5($source).'.sql'); 
    }

    /**
     * @method SchemaInterface getTableName
     * @return string
     *
     * This method returns a proper formatted table name for a driver
     * @throws ClassNotFound
     */
	public function getTableName() : string
	{
        // @var string $table
        $table = $this->table == null ? $this->tableName : $this->table;
        
        // return string
		return ClassManager::singleton(Query::class)->getTableName($table);
    }

    /**
     * @method Schema getWhatChanged
     * @param array $entryNew
     * @param array $entryOld
     * @return void
     */
    private function getWhatChanged(array $entryNew, array $entryOld, &$query = []) : void
	{
        // @var array $newKeys
		$newKeys = array_keys($entryNew);
		$oldKeys = array_keys($entryOld);

        // @var bool $oldHasColumn
		$oldHasColumn = true;

        // @var array $sqlArray
		$sqlArray = [];

		foreach ($newKeys as $index => $column) :
		
			if (!in_array($column, $oldKeys)) $oldHasColumn = false; break;
            
		endforeach;

		// if old has column is true
		if ($oldHasColumn) :
		
			// check position of new column
			foreach ($oldKeys as $index => $column) :
			
				if (isset($newKeys[$index])) :
                
                    // @var string $newcolumn
                    $newcolumn = $newKeys[$index];
                    
					if ($newcolumn != $column) :
					
						// get column line
                        $line = $entryNew[$newcolumn]['config'];

                        // drop tmp table
                        $sqlArray[] = "DROP TABLE IF EXISTS ___temporary__table;";

                        // rename table 
                        $sqlArray[] = "ALTER TABLE `{$this->tableName}` RENAME TO ___temporary__table;";

                        // create table
                        $sqlArray[] = $this->sqlStatement . ';';

                        // copy back the records

                        
						// change column
						$sqlArray[] = "ALTER TABLE `{$this->tableName}` ALTER COLUMN {$newcolumn} {$newcolumn} {$line} AFTER {$column};";
						
                    else:

						// check line
						$newLine = $entryNew[$newcolumn]['config'];
						$oldLine = $entryOld[$newcolumn]['config'];

                        // statement changed
                        if ($newLine != $oldLine) :

                            $sqlArray[] = "ALTER TABLE `{$this->tableName}` ALTER COLUMN {$newcolumn} TYPE $newLine;";

                        endif;
                    
                    endif;
			
                else:
				
                    $sqlArray[] = "ALTER TABLE `{$this->tableName}` DROP COLUMN $column;";
                    
                endif;
                
			endforeach;
		
		else:
		
			foreach ($oldKeys as $index => $column) :
            
                if (isset($newKeys[$index]) && $this->updateSchemaFromExternal === false) :

                    // @var string $newcolumn
                    $newcolumn = $newKeys[$index];
                    
                    if ($newcolumn != $column) :
                        
                        // update $sqlArray
                        $newLine = $entryNew[$newcolumn]['config'];
                        
                        // add to sqlarray
                        $sqlArray[] = "ALTER TABLE `{$this->tableName}` RENAME COLUMN $column TO $newcolumn;";
                        $sqlArray[] = "ALTER TABLE `{$this->tableName}` ALTER COLUMN $newcolumn TYPE $newLine;";
                        
                    endif;

                else:

                    

                endif;
                
            endforeach;

            // clean up
            unset($oldKeys, $newcolumn);
            
		endif;

        // update $query
		$query = $sqlArray;
    }
    
    /**
     * @method Schema addNewColumn
     * @param array $entryNew
     * @param array $query
     * @param int $entryOldLen
     * @return void
     */
    private function addNewColumn(array $entryNew, array &$query, int $entryOldLen) : void
    {
        // @var int $index
        $index = 0;

        // @var array $newKeys
        $newKeys = array_keys($entryNew);

        // get all entries
        foreach ($entryNew as $column => $line) :
        
            if ($index >= $entryOldLen) :
        
                $after = isset($newKeys[$index-1]) ? $newKeys[$index-1] : null;

                if ($after !== null) $after = " AFTER {$after}";

                $lineInfo = $line['config'];

                // update query
                $query[] = "ALTER TABLE `{$this->tableName}` ADD {$column} {$lineInfo}{$after};";

            endif;
            
            $index++;

        endforeach;
    }
}