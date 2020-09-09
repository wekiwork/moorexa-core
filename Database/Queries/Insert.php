<?php
namespace Lightroom\Database\Queries;

use Lightroom\Database\QueryBuilder;
use Lightroom\Database\Cache\QueryCache;

/**
 * @package QueryBuilder Insert Query
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is the default insert builder. Can be replaced whenever
 */
trait Insert 
{
    /**
     * @method Insert insertStatement
     * @param array $arguments
     * @param string  $statement (like a markup that contains placeholders like '{values}', '{table}')
     * @return QueryBuilder
     */
    public function insertStatement(array $arguments, string $statement)
    {
        // run callback
        $CallbackClosure = $this->findCallbackFromArguments($arguments);

        // push insert data
        $this->argumentPassed = $arguments;

        // set mixedData
        $mixedData = null;

        // check if not hashed
        if (QueryCache::isCached('insert', func_get_args(), $mixedData) === false) :
            
            if (count($arguments) > 0) :
            
                // check if args 2 is string and possibly an object
                if (isset($arguments[1]) && is_string($arguments[1])) :
                
                    $object = json_decode($arguments[1]);
                    $copy = $arguments;

                    // build new args
                    $newArgs = [];

                    if (is_string($arguments[0]) && is_object($object)) :
                    
                        $columns = explode(',', $arguments[0]);

                        foreach ($object as $key => $val) :
                        
                            $row = [];

                            $row[trim($columns[0])] = $key;
                            $row[trim($columns[1])] = $val;

                            $newArgs[] = $row;

                        endforeach;

                        // update arguments
                        if (count($newArgs) > 0) $arguments = $newArgs;

                    endif;

                endif;

                // first argument passed
                $firstArgument = $arguments[0];

                // get rules data for object passed.
                if (is_object($firstArgument)):
                
                    if (method_exists($firstArgument, 'rulesHasData')) :
                    
                        // get return data
                        $firstArgument = $firstArgument->rulesHasData();

                        // array shift
                        $arguments[0] = $firstArgument;

                    endif;

                endif;

                // is object? convert to array
                if (is_object($firstArgument)) $firstArgument = func()->toArray($firstArgument);

                // json data?
                if (is_string($firstArgument) && trim($firstArgument[0]) == '{' ) :
                
                    // convert to an array
                    $firstArgument = func()->toArray(json_decode($firstArgument));
                    $arguments[0] = $firstArgument;

                endif;

                // continue with array
                if (is_array($firstArgument)) :

                    // get insert header
                    $getHeader = $this->__arrayInsertHeader($arguments);

                    $header = $getHeader['header'];
                    $structureArray = $getHeader['structure'];

                    // update insert keys
                    $this->insertKeys = $header;

                    // update statement
                    $statement = str_replace('{column}', $header, $statement);

                    // get insert body
                    $data = $this->__arrayInsertBody($arguments, $structureArray);
                    $bind = $data['bind'];
                    $values = $data['values'];

                    // update statement
                    $statement = str_replace('{query}', $values, $statement);

                    // update query and bind
                    $this->query = $statement;
                    $this->bind = $bind;
                
                else:
                    // string
                    // no equal ?
                    if (strpos($firstArgument, '=') === false) :
                    
                        $structureArray = explode(',', $firstArgument);

                        // update statement
                        $statement = str_replace('{column}', $firstArgument, $statement);

                        // update insert keys
                        $this->insertKeys = $firstArgument;

                        // update cursor
                        array_shift($arguments);

                        // data passed
                        if (isset($arguments[0])) :
                        
                            $firstArgument = $arguments[0];

                            // is object? convert to array
                            if (is_object($firstArgument)) $firstArgument = func()->toArray($firstArgument);

                            // json data? convert to array
                            if (is_string($firstArgument) && trim($firstArgument[0]) == '{' ) $firstArgument = func()->toArray(json_decode($firstArgument));

                            // @var bool $continue
                            $continue = true;

                            // continue with array
                            if (is_array($firstArgument)) :
                            
                                if (count($arguments) != 1) :
                                
                                    // update $continue
                                    $continue = false;

                                    // get insert body
                                    $data = $this->__arrayInsertBody($arguments, $structureArray);

                                    $bind = $data['bind'];
                                    $values = $data['values'];

                                    // update statement
                                    $statement = str_replace('{query}', $values, $statement);

                                    // update query and bind
                                    $this->query = $statement;
                                    $this->bind = $bind;
                                
                                else:
                                
                                    $arguments = $firstArgument;

                                endif;
                            
                            else:
                            
                                if (count($arguments) > 0) $continue = true;

                            endif;


                            if ($continue) :
                            
                                $this->addQueryAndBindForInsert($statement, $structureArray, $arguments);

                            endif;
                        else:
                        
                            $this->addQueryAndBindForInsert($statement, $structureArray, $arguments);

                        endif;
                    
                    else:
                    
                        $data = $this->__stringInsertBind($firstArgument);

                        // update statement
                        $statement = str_replace('{column}', implode(',', $data['header']), $statement);
                        $statement = str_replace('{query}', '('.implode(',', $data['values']).')', $statement);

                        $bind = $data['bind'];
                        $this->insertKeys = $data['header'];

                        // update cursor
                        array_shift($arguments);

                        // add bind
                        $this->__addBind($arguments, $bind);

                        // update bind and query
                        $this->bind = $bind;
                        $this->query = $statement;

                    endif;

                endif;
            
                // save now
                $mixedData !== null ? $mixedData($this->query, $this->bind) : null;
                
            else:
            
                $this->errorPack['insert'][] = 'No data to insert. You can pass compound data types.';

            endif;

        else:

            // use from cache
            $this->query = $mixedData['query'];
            $this->bind = $mixedData['bind'];

        endif;
        
        // run callback closure
        $this->runStatementClosure($CallbackClosure);

        return $this;
    }

    /**
     * @method Insert addQueryAndBindForInsert
     * @param string $statement
     * @param array $structureArray
     * @param array $arguments
     * 
     * This updates the query statement and add binds for PDO prepared statement
     */
    private function addQueryAndBindForInsert(string $statement, array $structureArray, array $arguments)
    {
        static $staticIndex = 0;

        $values = [];
        $binds = [];
        $length = count($structureArray)-1;

        // @var int $localIndex
        $localIndex = 0;

        // update argument
        if (count($structureArray) > count($arguments)) :
        
            // update arguments
            foreach ($structureArray as $index => $h) if (!isset($arguments[$index])) $arguments[$index] = null;
            
        endif;

        $length--;

        // @var array $value
        $value = [];

        // read arguments 
        foreach ($arguments as $argument) :
        
            // update structure array
            $structureArray[$localIndex] = trim($structureArray[$localIndex]);

            // add to value array
            $value[$localIndex] = ':'.$structureArray[$localIndex].$staticIndex;

            // update binds
            $binds[$structureArray[$localIndex].$staticIndex] = addslashes(htmlentities($argument, ENT_QUOTES, 'UTF-8'));

            // update pointer
            if ($localIndex == count($structureArray)-1 || $localIndex == count($arguments)-1) :
            
                $localIndex = 0;
                $values[] = '('.implode(',', $value).')';
            
            else:
            
                // increment local index 
                $localIndex++;

            endif;

            $staticIndex++;

        endforeach;

        // reset pointer
        $staticIndex = 0;

        // update statement
        $statement = str_replace('{query}',implode(',', $values), $statement);

        // update query and bind
        $this->query = $statement;
        $this->bind = $binds;
    }
}