<?php
namespace Lightroom\Database\Drivers\Sqlite;

use Closure;
use Lightroom\{
    Core\FunctionWrapper, Common\File, 
    Adapter\ClassManager, Exceptions\ClassNotFound
};
use Lightroom\Database\{
    Interfaces\SchemaInterface, Drivers\SchemaHelper,
    Drivers\SchemaProperties, Interfaces\SchemaHelperInterface
};
use ReflectionException;

/**
 * @package Sqlite Schema class
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is the default Schema class for Sqlite database system
 */
class Schema implements SchemaInterface, SchemaHelperInterface
{
    // use schema helper 
    use SchemaProperties, SchemaHelper;
    
    /**
     * @var string $_datatypes
     */
    public static $_datatypes = 'bigint,int,smallint,tinyint,bit,decimal,numeric,money,smallmoney,float,real,datetime,datetime2,date,datetimeoffset,smalldatetime,char,varchar,text,nchar,nvarchar,ntext,binary,varbinary,image,cursor,sql_variant,table,timestamp,uniqueidentifier,integer';

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
        return "ALTER TABLE `{$table}` RENAME TO `{$newName}`";
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
        return "CREATE TABLE `{$this->tableName}` (";
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
        // set string
        $this->string = 'nvarchar';

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
     * @method Schema foreign
     * @param string $column
     * @return void 
     */
    public function foreign(string $column) : void 
    {
        $this->buildQuery[] = $this->findClosureFunctionAndReturnStatement(func_get_args(), "\t" . ' FOREIGN KEY ('.$column.') ');
        $this->queryInfo['foreign'] = [$column, '', ''];
    }

    /**
     * @method Schema primary
     * @param array $arguments
     * @return void 
     */
    public function primary(...$arguments) : void 
    {
        // @var string $command
        $command = 'PRIMARY KEY';

        // @var string $arguments
        $arguments = count($arguments) > 0 ? '('.implode(',', $arguments).')' : '';

        $this->buildQuery[] = $this->findClosureFunctionAndReturnStatement(func_get_args(), "\t" . $command . $arguments);
        $this->queryInfo['primary'] = [$command, $arguments];
    }

    /**
     * @method Schema decimal
     * @param string $column
     * @param int $precision
     * @param int $scale
     * @return void 
     */
    public function decimal(string $column, int $precision = 18, int $scale = 0) : void 
    {
        $this->buildQuery[] = $this->findClosureFunctionAndReturnStatement(func_get_args(), "\t" .$column . ' DECIMAL('.$precision.', '.$scale.'), ');
        $this->queryInfo['decimal'] = [$column, $precision, $scale];
    }

    /**
     * @method Schema references
     * @param string $table
     * @param string $column
     * @return void 
     */
    public function references(string $table, string $column) : void 
    {
        $this->buildQuery[] = $this->findClosureFunctionAndReturnStatement(func_get_args(), "\t" . ' REFERENCES '.$table.' ('.$column.') ');
        $this->queryInfo['references'] = [$table, $column, ''];
    }

    /**
     * @method Schema on
     * @param string $action
     * @param string $instruction
     * @return void 
     */
    public function on(string $action, string $instruction) : void 
    {
        $this->buildQuery[] = $this->findClosureFunctionAndReturnStatement(func_get_args(), "\t" . ' ON '.strtoupper($action).' '.strtoupper($instruction).' ');
        $this->queryInfo['on'] = [$action, $instruction, ''];
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
        $method = 'integer';
        $other = 'PRIMARY KEY AUTOINCREMENT';
    }

    /**
     * @method SchemaHelperInterface __unique
     * @param string $column
     * @return void
     */
    public function __unique(string $column, string &$statement = '') : void
    {
        $statement = 'UNIQUE';
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
        $this->runCommandStatement('CREATE TABLE', $statement);
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
        return get_path(func()->const('database'), '/Sql/schema_sqlite_'.md5($source).'.sql'); 
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

        // get query instance
        $query = ClassManager::singleton(Query::class);

        // get columns
        $tableInfo = $query->sql('SELECT * FROM pragma_table_info("'.$this->tableName.'")')->fetchAll();

        // @var array $sqlArray
        $sqlArray = [];
        
        // get columns
        $columns = [];

        // push column
		foreach ($tableInfo as $info) $columns[$info['name']] = null;
        
        // table instruction
        $instruction = ['change' => [], 'drop' => []];

        // get difference
        $diffNew = array_diff($newKeys, $oldKeys);
        $diffOld = array_diff($oldKeys, $newKeys);

        if (count($diffNew) > 0) :

            // drop columns
            foreach ($diffOld as $index => $column) :

                if (!isset($diffNew[$index]) && array_key_exists($column, $columns)) :

                    // drop column
                    $instruction['drop'][] = [
                        'column' => $column
                    ];

                endif;
                
            endforeach;

            // change column
            foreach ($diffNew as $index => $column) :

                // get data type
                $newLine = $entryNew[$column]['config'];

                // column name
                $columnName = !isset($diffOld[$index]) ? $column : $diffOld[$index];

                // update column
                $instruction['change'][] = [
                    'column' => $columnName,
                    'newColumn' => $column,
                    'line' => $newLine
                ];
                
            endforeach;

        endif;
        
        // check instruction
        if (count($instruction['change']) > 0 || count($instruction['drop']) > 0) :

            $this->replaceColumnInfo([
                'instruction' => $instruction,
                'tableInfo' => $tableInfo
            ], $sqlArray);

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

        // table instruction
        $instruction = ['change' => [], 'drop' => []];

        // get all entries
        foreach ($entryNew as $column => $line) :
        
            if ($index >= $entryOldLen) :
        
                // add column
                $instruction['change'][] = [
                    'column' => $column,
                    'newColumn' => $column,
                    'line' => $line['config']
                ];

            endif;
            
            $index++;

        endforeach;

        // check instruction
        if (count($instruction['change']) > 0 ) :

            // get columns
            $tableInfo = ClassManager::singleton(Query::class)->sql('SELECT * FROM pragma_table_info("'.$this->tableName.'")')->fetchAll();

            $this->replaceColumnInfo([
                'instruction' => $instruction,
                'tableInfo' => $tableInfo
            ], $query);

        endif;
    }

    /**
     * @method Schema replaceColumnInfo
     * @param array $columnData
     * @param array $sqlArray
     */
    private function replaceColumnInfo(array $columnData, array &$sqlArray) : void
    {
        // get variables
        extract($columnData);

        // @var array $columns
        $columns = [];

        // @var array $newColumns
        $newColumns = [];

        // push columns information
        foreach ($tableInfo as $info) :

            // get column value
            $columnValue = $info['name'];

            // check drop instruction
            foreach ($instruction['drop'] as $drop) if ($drop['column'] == $columnValue) $columnValue = null;

            // add to columns
            if ($columnValue !== null) :

                 // push column
                $columns[] = $columnValue;

                // get from instruction
                foreach ($instruction['change'] as $change) if ($change['column'] == $columnValue) $columnValue = $change['newColumn'];

                // push new column
                $newColumns[] = $columnValue;

            endif;

        endforeach;

        // run change instruction
        $sql = $this->sqlStatement;

        // turn off foreign key
        $sqlArray[] = 'PRAGMA foreign_keys=off;';

        // drop table
        $sqlArray[] = 'DROP TABLE IF EXISTS __temporary_table__;';

        // begin transaction
        //$sqlArray[] = 'BEGIN TRANSACTION;';

        // rename table sql
        $sqlArray[] = "ALTER TABLE `{$this->tableName}` RENAME TO __temporary_table__;";

        // add create statement
        $sqlArray[] = $sql . ';';

        // insert records
        $sqlArray[] = "INSERT INTO {$this->tableName} (".implode(',', $newColumns).")\n
        SELECT ".implode(',', $columns)." FROM __temporary_table__; ";

        // drop temp table
        $sqlArray[] = "DROP TABLE __temporary_table__;";

        // enable foreign key
        $sqlArray[] = 'PRAGMA foreign_keys=on;';
    }
}