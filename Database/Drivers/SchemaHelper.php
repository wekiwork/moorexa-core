<?php
namespace Lightroom\Database\Drivers;

use Closure;
use Lightroom\{
    Common\File, Core\FunctionWrapper, Adapter\ClassManager,
    Database\Interfaces\SchemaInterface
};
/**
 * @package Schema Helper forr drivers
 */
trait SchemaHelper
{
    /**
     * @var array $sqljob
     */
    public $sqljob = [];

    /**
     * @var string $tableName
     */
    public $tableName = '';

    /**
     * @var string $table (alias of $tableName)
     */
    public $table = '';

    /**
     * @var string $sqlString
     */
    public $sqlString = '';

    /**
     * @var string $datatypes
     */
    public static $datatypes = 'INT,VARCHAR,TEXT,DATE,TINYINT,SMALLINT,MEDIUMINT,INT,BIGINT,DECIMAL,FLOAT,DOUBLE,REAL,BIT,BOOLEAN,SERIAL,DATE,DATETIME,TIMESTAMP,TIME,YEAR,CHAR,VARCHAR,TINYTEXT,TEXT,MEDIUMTEXT,LONGTEXT,BINARY,VARBINARY,TINYBLOB,MEDIUMBLOB,BLOB,LONGBLOB,ENUM,SET,GEOMETRY,POINT,LINESTRING,POLYGON,MULTIPOINT,MULTILINESTRING,MULTIPOLYGON,GEOMETRYCOLLECTION,JSON';

    /**
     * @var string $string
     */
    private $string = 'varchar';

    /**
     * @var string $notNull
     */
    private $notNull = 'not_null';

    /**
     * @var string $comment
     */
    private $comment = 'comment';

    /**
     * @var string $sqlStatement
     */
    private $sqlStatement = '';
    
    /**
     * @method SchemaInterface getDataTypes
     * @return array
     * 
     * This method returns all supported data types for a driver
     */
	public static function getDataTypes() : array
	{
        // get data types
        $types = strtolower(self::$datatypes);
        
        // return array
		return explode(',', $types);
    }

    /**
     * @method SchemaInterface sql
     * @param Closure $callback
     * @return void
     * 
     * This method takes a closure function and runs a query on the return value of that closure
     */
    public function sql(Closure $callback) : void
	{
		if (is_callable($callback)) :
        
            // get sql
			$sql = call_user_func($callback, $this->tableName);
			$sql = preg_replace('/\n{1,}\s{1,}/',"\n", $sql);
			$sql = trim($sql);
            $sql = rtrim($sql, ';');
            
            // update schema
            $this->sqlString = $sql;
            
        endif;
    }
    
    // database schema
	public function ___schema($tabledata = null)
	{
		if ($tabledata !== null) :
        
            // @var string $tableNames
			$tablename = $this->tableName;

            // check if $tabledata is a clossure fucntion
			if ($tabledata !== null && is_callable($tabledata)) :
			
				$table = new Table();
				$schema = new Schema();

				call_user_func($tabledata, $table);

				if (count($table->__tabledata) > 0) :
				
                    // @var array $sql
					$sql = [];

                    // updata sql
					foreach ($schema->__tabledata as $index => $data) $sql[] = $index . ' ' . $data[0].',';

                    // update schema
					$schema->tableName = $tablename;
					$schema->buildQuery = $sql;
					$schema->saveSchema();

                    // get jobs
					$job = $schema->sqljob;

					if (count($job) > 0) :
					
						// run query
						$success = 0;
						$failed = 0;
						
						foreach ($job as $j => $sql) :
						
							$sql = rtrim($sql, "; ");
							$sql = rtrim($sql, ";");
							$sql = rtrim($sql, ";\n");

							$this->sqlString = $sql;
                            $this->saveSchema();
                            
                        endforeach;

                    endif;
                    
                endif;

                // clean up
                unset($table, $schema);
                
            endif;
            
		endif;
	}

    /**
     * @method SchemaInterface addJobAndSave
     * @param array $jobs
     * @param string $newSql
     * @return void
     * 
     * This method takes a list of sql jobs and saves it to a file
     */
    public function addJobAndSave(array $jobs, string $newSql) : void
	{
		// get save path
        $savePath = $this->getSqlSavePath(); 
        
        // remove duplicates
        $jobs = array_unique($jobs);

		if (count($jobs) > 0) :
		
			foreach ($jobs as $index => $sql) :
			
                // add $sql to jobs
                $this->sqljob[] = $sql;
                
                // append statement
                File::append("\n".$sql, $savePath);
                
            endforeach;

		endif;
		
		// remove question tag
		if (strpos($newSql, '-%ques')) $newSql = str_replace('-%ques', ',', $newSql);
        
        // add to jobs
        $this->sqljob[] = $newSql;
        
        // append statement
		File::append("\n".$newSql, $savePath);
		
    }

    /**
     * @method SchemaInterface options
     * @param Closure $callback
     * @return void
     * 
     * This method prepares an sql statement to alter a table with options
     */
    public function options(Closure $callback) : void
    {
        if (isset($this->tableOptions[$this->tableName])) :
        
            // @var array $parameters
            $parameters = [];
            
            // flag option
            $this->tableOptionsUsed = true;
            
            // get parameters
            FunctionWrapper::getParameters($callback, $parameters, [$this]);
            
            // call closure function
            call_user_func_array($callback, $parameters);
            
        endif;
    }

    /**
     * @method SchemaInterface engine
     * @param string $engineName
     * @return void
     * 
     * This method attempts to change the engine of the current table.
     * Implementing class must implement __engine(string $table, string $engine) method and must return a valid sql statement.
     */
    public function engine(string $engineName) : void
    {
        if ($this->tableOptionsUsed) :
        
            // get table name
			$table = $this->tableName;
            $engine = strtoupper($engineName);
            
            // update schema
            $this->sql = $this->__engine($table, $engine);
            
            // add to jobs
			$this->sqljob[] = $this->sql;
            
            // append sql to end of file
            File::appendToEnd("\n".$this->sql.";", $this->getSqlSavePath());

            // clean sql
            $this->sql = '';
            
		endif;
    }

    /**
     * @method SchemaInterface rename
     * @param string $newName
     * @return void
     * 
     * This method attempts to rename a table with a new name.
     * Implementing class must implement a method __rename(string $table, string $newName) that returns a valid Alter Statement for table.
     */
    public function rename(string $newName) : void
    {
        if ($this->tableOptionsUsed) :
        
            // flag option
            $this->tableRename[$this->tableName] = $newName;
            
            // get table name
            $table = $this->tableName;

            // update schema
            $this->sql = $this->__rename($table, $newName);
            
            // add to jobs
			$this->sqljob[] = $this->sql;
            
            // append sql to end of file
            File::appendToEnd("\n".$this->sql.";", $this->getSqlSavePath());
            
            // update schema
			$this->sql = '';
            $this->tableName = $newName;
            
		endif;
    }

    /**
     * @method SchemaInterface collation
     * @param string $collation
     * @return void
     * 
     * This method attempts to change the character set and the default collation of the current table
     * Implementing class must implement __collation(string $table, string $charset, string $collation) method and must return a valid sql statement.
     */
    public function collation(string $collation) : void
    {
        if ($this->tableOptionsUsed) :
        
            // @var string $table
            $table = $this->tableName;
            
            // @var string $charset
            $charset = 'utf8';

            // update charset   
			if (strpos($collation, '_') !== false) $charset = strtolower(substr($val, 0, strpos($collation, '_')));

            // update schema
            $this->sql = $this->__collation($table, $charset, $collation);

            // add to jobs
			$this->sqljob[] = $this->sql;

            // append sql to end of file
            File::appendToEnd("\n".$this->sql.";", $this->getSqlSavePath());

            // update sql
            $this->sql = '';
            
		endif;
    }

    /**
     * @method SchemaInterface saveSchema
     * @return SchemaInterface
     * 
     * This method saves a schema to a file. It can also check what changed and write to the list of jobs to be ran for the current table.
     * Implementing class must implement a method __createStatement() that returns a valid CREATE Statement for driver.
     */
    public function saveSchema() : SchemaInterface
    {
        // @var string $sqlStatement
        $sqlStatement = $this->sqlString;

        // check if $sqlString is not empty
        if ($this->sqlString == "") :
        
            // update sql statement
            $sqlStatement = "\n". $this->__createStatement() . "\n";

            // hold the first line
            $firstLine = $sqlStatement;

            // update sqlstatement
            foreach ( $this->buildQuery as $index => $query ) $sqlStatement .= $query . "\n";

            $sqlStatement = rtrim($sqlStatement, ", \n");

            // add ) to end statement
            $sqlStatement .= "\n)";

        endif;

        // trim sql statement
        $sqlStatement = trim($sqlStatement);

        // update create SQL
        $this->createSQL = $sqlStatement;

        // hold a copy of $sqlStatement
        $save = $sqlStatement;

        // @var string $build
        $build = "";

        // @var string $data
        $data = "";

        // @var bool $justContinue
        $justContinue = false;

        // @var string $sqlPath
        $sqlPath = $this->getSqlSavePath();

        // create file if it doesn't exists
        if (!file_exists($sqlPath)) File::write('', $sqlPath);

        // save job to $sqlPath
        $this->saveSQLJob($sqlPath, $sqlStatement);

        // return instance
        return $this;
    }

    /**
     * @method SchemaInterface dropSchema
     * @return SchemaInterface
     * 
     * This method saves a drop statement to a file and also writes to the list of jobs for the current table
     */
    public function dropSchema() : SchemaInterface
    {
        if (strlen($this->sql) > 4) :

            // append sql to end of file
            File::append("\n".$this->sql.";", $this->getSqlSavePath());

        endif;

        // return schema
        return $this;
    }

    /**
     * @method SchemaInterface saveSQLJob
     * @param string $sqlPath
     * @param string $sqlStatement
     * @return void
     */
    private function saveSQLJob(string $sqlPath, string $sqlStatement)
    {
        // read sql content
        $sql = file_get_contents($sqlPath);

        // save sqlStatement to path
        if (empty($sql)) :
        
            // update sqlStatement
            if (strpos($sqlStatement, '-%ques') !== false) $sqlStatement = str_replace('-%ques', ',', $sqlStatement);

            // write to sqlPath
            File::write($sqlStatement . ';' . "\n", $sqlPath);

            // add job
            $this->sqljob[] = $sqlStatement;

        else:

            // push sql statement
            $this->sqlStatement = $sqlStatement;

            // @var string $firstLine
            $firstLine = $this->__createStatement();
						
            // get sql content
            $content = trim($sql);

            // where it all start. get last entry
            $begin = strrpos($content, $firstLine);

            if ($begin !== false) :
            
                // extract content from this point.
                $entry = substr($content, $begin);
                $entry = substr($entry, 0, strpos($entry, ');')).");";

                // we hash both strings and be sure something changed
                $hash_new_entry = md5($sqlStatement . ';');
                $hash_old_entry = md5($entry);

                // now we compare change
                if ($hash_new_entry != $hash_old_entry) :
                
                    // things changed.
                    // let's find out more.

                    // 1# remove create table if not exists from both string
                    $entry = str_replace($firstLine, '', $entry);
                    $newEntry = str_replace($firstLine, '', $sqlStatement);

                    // 2# remove closing braces
                    $entry = trim(rtrim($entry, ');'));
                    $newEntry = trim(rtrim($newEntry, ')'));

                    // 3# remove left padding from strings
                    $entry = preg_replace("/\n{1,}(\s*)/","\n", $entry);
                    $newEntry = preg_replace("/\n{1,}(\s*)/","\n", $newEntry);

                    // 4# get column definition
                    preg_match_all("/\w*\s{1,}((.*)?[,]|(.*)?\s*)/", $entry, $entryArray);
                    preg_match_all("/\w*\s{1,}((.*)?[,]|(.*)?\s*)/", $newEntry, $newEntryArray);

                    // create two new empty arrays
                    $entryOld = [];
                    $entryNew = [];

                    // 4.1# organize array with column as key
                    $this->___getEntry($entryOld, $entryArray);
                    $this->___getEntry($entryNew, $newEntryArray);

                    // check new entry size against old entry
                    if (count($entryNew) != count($entryOld)) :
                    
                        // ok check if new entry has a larger size
                        if (count($entryNew) > count($entryOld)) :
                        
                            // @var array $query
                            $query = [];

                            // get what changed first
                            $this->getWhatChanged($entryNew, $entryOld, $query);

                            // so we ilterate new entry to check what's on old column
                            $entryOldLen = count($entryOld);

                            // add new column
                            $this->addNewColumn($entryNew, $query, $entryOldLen);

                            // add job
                            $this->addJobAndSave($query, $sqlStatement.';');
                        
                        elseif (count($entryNew) < count($entryOld)) :

                            // @var array $query
                            $query = [];

                            // get what changed first
                            $this->getWhatChanged($entryNew, $entryOld, $query);
                            $this->addJobAndSave($query, $sqlStatement.';');

                        endif;
                    
                    else:
                    
                        // new entry is smaller or equal
                        // se we ilterate old entry to check what's changed
                        $query = [];
                        $this->getWhatChanged($entryNew, $entryOld, $query);
                        $this->addJobAndSave($query, $sqlStatement.';');

                    endif;

                endif;
            
            else :

                // update sqlStatement
                if (strpos($sqlStatement, '-%ques') !== false) $sqlStatement = str_replace('-%ques', ',', $sqlStatement);

                // append to sqlPath
                File::append("\n".ltrim($sqlStatement,"\n"). ';', $sqlPath);

                // add job
                $this->sqljob[] = $sqlStatement;

            endif;
                        
        endif;

        // make 
        $this->sqljob = array_unique($this->sqljob);
    }

    /**
     * @method Schema Helper
     * @param string $command
     * @param string $statement
     * @return void
     * 
     * This method takes a statement and adds it to the list of sql jobs
     */
    private function runCommandStatement(string $command, string $statement) : void
	{
		// @var string $table
        $table = $this->getTableName();
        
        // @var string $statement
		$statement = stripos($command, 'create') !== false ? "($statement)" : $statement;
        $statement = "$command `{$table}` $statement;";
        
		// add to job
		$this->sqljob[] = $statement;
    }
    
    /**
     * @method SchemaHelper ___getEntry
     * @param array $entry (reference)
     * @param array $current
     * @return void
     */
    private function ___getEntry(array &$entry, array $current) : void
	{
		foreach ($current[0] as $index => $line) :
		
			// get column
			$line = trim($line);
			$column = substr($line, 0, strpos($line, ' '));

			// remove trailing comma
			$line = rtrim($line, ',');

			// remove column from line
			$line = trim(ltrim($line, $column));

            // update line
			if (strpos($line, '-%ques') !== false) $line = str_replace('-%ques', ',', $line);

			// push entry
			$entry[$column]['config'] = $line;
        
        endforeach;
    }
    
    /**
     * @method SchemaHelper buildTableSchema
     * @param string $method
     * @param array $arguments
     * @return mixed
     * 
     * This method builds a table schema. The following methods must exists in the schema class
     * - __increment()
     * - __unique()
     * - __current()
     */
    private function buildTableSchema(string $method, array $arguments) 
    {
        // get closure function
        $closureFunction = null;
        
        foreach ($arguments as $index => $argument) :

            // check for closure function
            if ($argument !== null && is_callable($argument)) :

                // set closure function
                $closureFunction = $argument;
                
                // remove argument
                $arguments[$index] = null;

            endif;

        endforeach;

        // get column
        $column = isset($arguments[0]) ? $arguments[0] : null;

        // get length
        $length = isset($arguments[1]) ? is_numeric($arguments[1]) ? (int) $arguments[1] : "'$arguments[1]'" : '';
        
        // get other
		$other = isset($arguments[2]) && $arguments[2] !== null ? $arguments[2] : '';

        // update other
		if ( isset($arguments[1]) && is_string($arguments[1]) ) $other .= ' '. $arguments[1];

        // update method
		$method = strtolower($method);

		if ($method == 'increment') :
		
            $this->__increment($method, $length, $other);
             
        endif;

		if ($method == 'append') $method = "\n" .$arguments[0];

		if ($method == 'unique' && $column !== null) :
		
            // get unique
            $this->__unique($column);

            return $this;
            
        endif;

        // update method for string
		$method = $method == 'string' ? $this->string : $method;
        
        // get data types
        $types = $this->getDataTypes();
        

		if (in_array($method, $types)) :
		
			$this->number = $length;
			$this->other = $other;

			if ($method == $this->string && $length == "") $length = 255;

			if ($column !== null) :
			
				if ($length !== '') :
				
					$statement = "\t".$column.' ' . strtoupper($method) .'('. $length .') '. $other .', ';
					$queryInfo = [$method, $length, $other];
				
                else:
				
					$statement = "\t".$column.' ' . strtoupper($method).' '. $other .', ';
                    $queryInfo = [$method, '', $other];
                    
                endif;

                // load closure function
                if ($closureFunction !== null) $statement = $this->loadClosureFunctionFrom($closureFunction, $statement);
                
                // set statement
                $this->buildQuery[] = $statement;
                $this->queryInfo[$column] = $queryInfo;
                    
			endif;
	
        else:
            
            $last = $endinfo = '';
            $lastKey = $infoKey = 0;

			if (count($this->buildQuery) > 0) :
            
				$keys = array_keys($this->buildQuery);
				$lastKey = end($keys);
				$last = end($this->buildQuery);

				$info = end($this->queryInfo);
				$infokeys = array_keys($this->queryInfo);
				$infoKey = end($infokeys);

				$before = $last;

				$last = rtrim($last, ', ');
                $endinfo = $this->queryInfo[$infoKey][2];
            
            endif;
                
            // update length with column
            $length = $column;

            if ($method == 'not_null') $method = $this->notNull;

            if ($method == 'default') :
            
                $data = is_string($arguments[0]) ? "'{$arguments[0]}'" : $arguments[0];
                $data = is_string($data) ? str_replace(',', '-%ques', $data) : $data;

                if (is_bool($data)) :
                
                    if ($data === false):
                        $data = 0;
                    else:
                        $data = 1;
                    endif;

                endif;

                $last .= " ".$method .' '. $data .', ';
                $endinfo .= $method .' '. $data .',';
            
            elseif ($method == 'unique') :

                $unique = '';

                // @var string $unique
                $this->__unique('', $unique);

                $last .= " ".$unique.", ";
                $endinfo .= " ".$unique.", ";
            
            elseif ($method == 'current') :
            
                $last .= $this->__current();
                $endinfo .= $this->__current();
            
            elseif ($method == 'comment') :
            
                $last .= ' '.$this->comment.' \''.$arguments[0].'\', ';
                $endinfo .= ' '.$this->comment.' \''.$arguments[0].'\', ';
            
            elseif ($length !== '' && count($arguments) > 0) :
                
                $method = strtoupper($method);
                $last .= " ".$method .'('. implode(',', $arguments) .'), ';
                $endinfo .= $method .'('. implode(',', $arguments) .'),';
            
            else:
            
                $last .= " ".$method.", ";
                $endinfo .= " ".$method.", ";
                
            endif;
            
            // load closure function
            if ($closureFunction !== null) $last = $this->loadClosureFunctionFrom($closureFunction, $last);
            
            // load query
            $this->buildQuery[$lastKey] = $last;
            $this->queryInfo[$infoKey][2] = $endinfo;
                
            
            
        endif;
        
        // return array
        return [$method, $column, $length, $other];
    }

    /**
     * @method SchemaHelper findClosureFunctionAndReturnStatement
     * @param array $arguments
     * @param string $statement
     * @return string 
     */
    private function findClosureFunctionAndReturnStatement(array $arguments, string $statement) : string 
    {
        // get closure function
        $closureFunction = null;
        
        foreach ($arguments as $index => $argument) :

            // check for closure function
            if ($argument !== null && is_callable($argument)) :

                // set closure function
                $closureFunction = $argument;

                // remove argument
                $arguments[$index] = null;

            endif;

        endforeach;

        // clean argument
        $arguments = null;

        // load closure function
        if ($closureFunction !== null) $statement = $this->loadClosureFunctionFrom($closureFunction, $statement);
                
        // return string
        return $statement;
    }

    /**
     * @method SchemaHelper loadClosureFunctionFrom
     * @param Closure $schemaClosure
     * @param string $statement
     */
    private function loadClosureFunctionFrom(Closure $schemaClosure, string $statement) : string 
    {
        // trim off white space and also remove trailing comma
        $statement = preg_replace('/[,]$/', '', trim($statement));

        // copy build query
        $buildQuery = $this->buildQuery;

        // copy query info
        $queryInfo = $this->queryInfo;

        // now we reset them
        $this->buildQuery = $this->queryInfo = [];

        // create index
        $index = 2;

        // now we call the closure function
        call_user_func($schemaClosure->bindTo($this, static::class));

        // get data
        foreach ($this->buildQuery as $query) :

            // trim off white space and also remove trailing comma
            $query = preg_replace('/[,]$/', '', trim($query));

            // append to statement
            $statement .= "\n" . str_repeat("\t", $index) . $query ;

            // increment index
            $index++;

        endforeach;

        // recover
        $this->buildQuery = $buildQuery;
        $this->queryInfo = $queryInfo;

        // return string
        return $statement . ', ';
    }
    
}