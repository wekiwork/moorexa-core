<?php
namespace Lightroom\Database;

use PDO, PDOStatement;
use Lightroom\Exceptions\{
    QueryBuilderException, ClassNotFound
};
use Lightroom\Adapter\ClassManager;
use Lightroom\Database\DatabaseChannel;
use Lightroom\Database\DatabaseHandler as Handler;
/**
 * @package QueryBuilderExecute Queries
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait QueryBuilderExecute
{
    /**
     * @var mixed $defaultPromise
     */
    private static $defaultPromise;

    /**
     * @var bool $returnPromise
     * This would instruct the query builder to return a clean promise
     */
    public $returnPromise = false;

    /**
     * @var null $returnNewQuery
     * If not null, query builder would return this query
     */
    public $returnNewQuery = null;

    /**
     * @var bool $failed
     * This would change to true if query builder experienced a problem building the query
     */
    private $failed = false;

    /**
     * @var array $queries
     * This holds a record of queries ran
     */
    private static $queries = [];

    /**
     * @var array $statementsCached
     * This holds a record of statements executed
     */
    private static $statementsCached = [];
    
    /**
     * @var array $executeQueryCache
     * This holds a record of executed queries
     */
    private static $executeQueryCache = [];

    /**
     * @method QueryBuilder go
     * Execute query
     * @return PDOStatement
     */
    public function go() : PDOStatement
    {
        // return promise
        if ($this->returnPromise === true) :

            // return a clean promise
            return $this->cleanDefaultPromise();

        endif;

        // query not null
        if ($this->returnNewQuery !== null) :

            // return a new query
            return $this->returnNewQuery;

        endif;

        
        // not failed
        if ($this->failed === false) :

            // call Prefix Query method
            $this->callPrefixQuery($this);

            // query not null
            if ($this->returnNewQuery !== null) :

                // return a new query
                return $this->returnNewQuery;

            endif;

            // hash name
            $hashname = md5($this->query . implode('', $this->bind));
            
            // not cached
            if (!isset(self::$executeQueryCache[$hashname])) :
                
                // process request.
                if ($this->__checkForErrors($this->method) === true) :
                
                    // good
                    if ($this->method == 'select') :
                    
                        // fill in the gap
                        foreach ($this->bind as $key => $val) :
                        
                            if (is_null($val) || (is_string($val) && strlen($val) == 0)) :
                            
                                foreach ($this->bind as $value) :
                                
                                    if (!empty($value)) :
                                    
                                        $this->bind[$key] = $value;
                                        break;

                                    endif;

                                endforeach;

                            endif;

                        endforeach;
                        

                    endif;

                    // remove placeholder {where}
                    $this->query = str_replace('{where}', '', $this->query);

                    // remove placeholder {-where-}
                    $this->query = str_replace('{-where-}', '', $this->query);

                    
                    if (!$this->allowHTMLTags) :
                    
                        // get binds
                        $bind = $this->bind;

                        foreach ($bind as $key => $value) :
                        
                            if (is_array($value) || is_object($value)) :
                            
                                foreach ($value as $index => $val) if (is_string($val)) $value[$index] = strip_tags($val);

                                $bind[$key] = $value;
                            
                            elseif (is_string($value)) :
                            
                                $bind[$key] = strip_tags($value);

                            endif;

                        endforeach;

                        // update bind
                        $this->bind = $bind;

                    endif;

                    // cache
                    self::$executeQueryCache[$hashname] = [$this->query, $this->bind];
                    
                endif;

            else:

                list($this->query, $this->bind) = self::$executeQueryCache[$hashname];

            endif;
  
            // listening events
            $this->queryListenerFor(); 

            // method exists
            if ($this->method != '') : 

                try
                {
                    // check if query is allowed
                    if ($this->allowQuery()) :

                        // prepare statement
                        $statement = $this->___prepare($this->query);

                        // execute statement
                        if ($statement->execute()) :

                            switch ($this->method) :
                        
                                case 'insert':
                                case 'update':
                                case 'delete':
                                    if (!defined('DB_TEST_ENV')) $this->saveQueryStatement($this->query, $this->bind);
                                    $this->queryCachePath = null;
                                break;
        
                            endswitch;

                            // query ran
                            Handler::queryRanSuccessfully($statement, $this->method);

                            // reset
                            $this->query = '';
                            $this->bind = [];

                            // commit transaction
                            if (method_exists($this->pdoInstance, 'commit') && $this->method == 'select') :
                            
                                if ($this->pdoInstance->inTransaction()) : $this->pdoInstance->commit(); endif;

                            endif;

                            // load subscribers
                            if ($this->method != 'select') Handler::loadSubscribers($statement, $this->pdoInstance);
                            
                        endif;

                        // update method
                        $this->method = '';

                        // save for last query ran
                        self::$lastQueryRan = $statement;

                    else:
                        $statement = ClassManager::singleton(PDOStatement::class);
                    endif;
                }
                catch(\Throwable $exception)
                {
                    if (method_exists($this->pdoInstance, 'inTransaction') && $this->pdoInstance->inTransaction() === true) :

                        // rollback transaction
                        $this->pdoInstance->rollBack();

                    endif;

                    // show error
                    Handler::errorManager($exception);

                    // fallback statement
                    $statement = ClassManager::singleton(PDOStatement::class);
                }

                // return statement
                return $statement;

            endif;

        else:

            throw new QueryBuilderException('we experienced a problem building your query');

        endif;        
    }

    /**
     * @method QueryBuilder ___prepare
     * @param string $query
     * @return PDOStatement
     * 
     * Prepares PDO query
     */
    private function ___prepare(string $query) : PDOStatement
    {
        if (strlen($query) > 4) :

            if ($this->pdoInstance !== null) :
                
                // use transactions.
                if (method_exists($this->pdoInstance, 'inTransaction') && $this->pdoInstance->inTransaction() === false) :
                    
                    // begin transaction
                    if (method_exists($this->pdoInstance, 'beginTransaction')) $this->pdoInstance->beginTransaction();

                endif;

                // get hash name
                $hashName = md5($query . implode(':', $this->bind));

                // check if query hasn't been cached
                if (!isset(self::$statementsCached[$hashName])) :

                    $order = [];
                    $bind = $this->bind;

                    $this->getBinds = $bind;
                    $this->getSql = $query;

                    $this->query = $query;

                    $smt = $this->pdoInstance->prepare($query);

                    // extracting from external bind configuration.
                    if (count(self::$bindExternal) > 0) :
                    
                        foreach (self::$bindExternal as $key => $val) :
                        
                            // setting bind up.
                            if (isset($bind[$key])) $bind[$key] = $val;

                        endforeach;

                    endif;

                    if (is_bool($smt)) :

                        if (env('bootstrap', 'debug_mode') === true) :
                            echo 'You have an error in your database query. Please check your error.log file' . "\n";
                        endif;

                        // get the info
                        $info = $pdoInstance->errorinfo();

                        // log error
                        logger('monolog')->error($info[2], ['statement' => $query]);

                    endif;


                    if (count((array) $bind) > 0 && is_object($smt)) :
                    
                        // @var int $index
                        $index = 0;

                        // update bind
                        foreach ($bind as $key => $val) :
                        
                            if (is_array($val) && isset($val[$index]))  :
                            
                                $val = $val[$index];
                                $index++;

                            endif;

                            // get value from closure
                            if (!is_null($val) && !is_string($val) && is_callable($val))  $val = call_user_func($val);

                            // bind string
                            if (is_string($val)) $smt->bindValue(':'.$key, $val, PDO::PARAM_STR);

                            // bind integer
                            if (is_int($val)) $smt->bindValue(':'.$key, $val, PDO::PARAM_INT);

                            // bind boolean
                            if (is_bool($val)) $smt->bindValue(':'.$key, $val, PDO::PARAM_BOOL);

                            // bind null
                            if (is_null($val)) $smt->bindValue(':'.$key, $val, PDO::PARAM_NULL);

                            // bind others
                            if (!is_array($val)) :
                            
                                if (!is_object($val)) :
                                
                                    $smt->bindValue(':'.$key, $val);
                                
                                else:
                                
                                    $smt->bindValue(':'.$key, null);

                                endif;
                            
                            else:
                            
                                $value = array_shift($val);
                                $smt->bindValue(':'.$key, $value);

                            endif;

                        endforeach;
                        
                    endif;

                    // cache statement
                    self::$statementsCached[$hashName] = $smt;

                else:

                    // get statement
                    $smt = self::$statementsCached[$hashName];

                endif;

                return is_object($smt) ? $smt : (new PDOStatement);

            endif;

        endif;

        return null;
    }

    /**
     * @method QueryBuilder __checkForErrors
     * @param string $command
     * @return bool
     * 
     * Check for errors in query
     */
    private function __checkForErrors(string &$command) : bool
    {
        // @var bool $free
        $free = true;

        // @var array $errors
        $errors = [];

        // check command
        switch ($command) :
        
            // update statement
            case 'update':

                if (preg_match('/({table})/', $this->query)) :
                
                    $free = false;
                    $errors[] = 'Table not found. Statement Construction failed.';

                endif;

                if (preg_match('/({query})/', $this->query)) :
                
                    $free = false;
                    $errors[] = 'Query not found. Statement Construction failed.';

                endif;

                if (preg_match('/({where})/', $this->query)) :
                
                    $free = false;
                    $errors[] = 'Where statement missing. Statement Construction failed.';

                endif;

            break;
            
        endswitch;
        

        if (count($errors) > 0) :
            
            // update error pack
            if (!isset($this->errorPack[$command])) $this->errorPack[$command] = [];

            $this->errorPack[$command] = array_merge($this->errorPack[$command], $errors);
            
        endif;

        // clean up
        $errors = null;

        // return bool
        return $free;
    }

    /**
     * @method QueryBuilder allowQuery
     * @return bool
     * 
     * This method will check if record has not been inserted previously, then do only if it has not been.
     */
    private function allowQuery() : bool
    {
        // @var bool $allowed
        $allowed = true;

        // continue with insert query
        if ($this->method == 'insert') :
        
            if ($this->allowedQueryCalled === false) :
            
                $db = $this->pdoInstance;

                // check if record doesn't exists.
                // to avoid repetition.
                // get columns
                $column = substr($this->query, strpos($this->query, '(')+1);
                $column = substr($column, 0, strpos($column, ')'));

                // convert to an array
                $array = explode(',', $column);

                // get binds
                $binds = $this->bind;

                // @var array $where
                $where = [];

                // continue if query is not empty
                if ($this->query != '') :
                
                    // get the columns
                    preg_match('/([(].*?[)])/', $this->query, $column);

                    if (isset($column[0])) :
                    
                        // remove bracket
                        $column = preg_replace('/[)|(]/','', $column[0]);

                        // build where statement
                        $columnArray = explode(',', $column);

                        // build where array
                        foreach ($columnArray as $column) $where[] = $column . ' = ?';
                        
                    endif;

                    // now start from values
                    $values = stristr($this->query, 'values');
                    $originalValue = $values;

                    // remove "VALUES"
                    $values = ltrim($values, 'VALUES ');

                    // now get all values and check database or remove from where statement
                    preg_match_all('/([(].*?[)])/', $values, $matches);
                    $newBind = [];
                    $newValues = [];

                    // get where
                    $where = implode(' AND ', $where);
                    $select = 'SELECT * FROM '.$this->table.' WHERE '.$where;

                    // run prepared statement
                    $statement = $db->prepare($select);

                    // so we have binds ?
                    if (count($matches[0]) > 0) :
                    
                        foreach ($matches[0] as $value) :
                        
                            $original = $value;
                            // remove bracket
                            $value = preg_replace('/[)|(|:]/','', $value);
                            $valueArray = explode(',', $value);

                            // @var array $bind
                            $bind = [];

                            // build bind from values
                            foreach($valueArray as $bindKey) :
                                
                                // get bind value
                                $bindVal = $this->bind[$bindKey];

                                // get bind value from closure
                                if (!is_null($bindVal) && !is_string($bindVal) && is_callable($bindVal)) :

                                    $bindVal = call_user_func($bindVal);

                                endif;

                                // update bind
                                $bind[] = $bindVal;

                            endforeach;

                            // execute statement
                            $statement->execute($bind);

                            // do record exists ?
                            if ($statement->rowCount() == 0) :
                            
                                // Update new value
                                $newValues[] = $original;

                                // build new bind for insert query after removing inserted records  
                                foreach ($valueArray as $bindKey) :
                                    
                                    // get bind value
                                    $bindVal = $this->bind[$bindKey];

                                    // get bind value from closure
                                    if (!is_null($bindVal) && !is_string($bindVal) && is_callable($bindVal)) $bindVal = call_user_func($bindVal);

                                    // update new bind
                                    $newBind[$bindKey] = $bindVal;

                                endforeach;
                                
                            endif;

                            // Close cursor
                            $statement->closeCursor();

                        endforeach;

                    endif;

                    // update allowed
                    $allowed = false;

                    // continue if we have new values
                    if (count($newValues) > 0) :
                        
                        // update bind
                        $values = implode(', ', $newValues);
                        $this->bind = $newBind;

                        // update query and prepare query 
                        $this->query = str_replace($originalValue, 'VALUES '.$values, $this->query);

                        // allow execution
                        $allowed = true;

                    endif;

                endif;

            endif;

        endif;

        return $allowed;
    }

    /**
     * @method QueryBuilder queryListenerFor
     * @return void
     */
    private function queryListenerFor() : void 
    {
        // get channel
        $channel = call_user_func([$this->driverClass, 'getChannel'], $this->driverClass);

        // has channel
        if (strlen($channel) > 1) :

            // copy instance
            $builder = $this;

            // load database channel class
            $databaseChannel = call_user_func_array([
                DatabaseChannel::class, 'loadInstance'
            ], [$this->driverClass, [
                'query'     => $this->query,
                'bind'      => $this->bind,
                'table'     => $this->table,
                'method'    => $this->method,
                'builder'   => $builder,
                'origin'    => 'builder'
            ]]);

            // call channel
            call_user_func_array([$channel, 'ready'], [$this->method, $databaseChannel]);

            // update query
            if (strlen($builder->query) <= strlen($this->query)) $this->query = $databaseChannel->getQuery();

            // update bind
            if (count($builder->bind) <= count($this->bind)) $this->bind = $databaseChannel->getBind();

        endif;
    }
}