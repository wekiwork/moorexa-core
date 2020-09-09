<?php
namespace Lightroom\Database\Queries;

use Lightroom\Database\QueryBuilder;
use Lightroom\Database\Cache\QueryCache;

/**
 * @package QueryBuilder Select Query
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is the default select builder. Can be replaced whenever
 */
trait Select 
{
    /**
     * @method Select selectStatement
     * @param array $arguments
     * @param string  $statement (like a markup that contains placeholders like '{where}', '{table}')
     * @return QueryBuilder
     */
    public function selectStatement(array $arguments, string $statement)
    {
        // run callback
        $CallbackClosure = $this->findCallbackFromArguments($arguments);

        // set mixedData
        $mixedData = null;
        
        // check if not hashed
        if (QueryCache::isCached('select', func_get_args(), $mixedData) === false) :

            // build select statement from string and array
            if (count($arguments) > 0) :

                // first argument passed
                $firstArgument = $arguments[0];

                // get rules data from object passed. (just in case)
                if (is_object($firstArgument)) :
                    
                    // check if method ruleHasData was passed. This belongs to input rules class
                    if (method_exists($firstArgument, 'rulesHasData')) :
                    
                        // get rules data
                        $firstArgument = $firstArgument->rulesHasData();

                        // replace the content of the first argument
                        $arguments[0] = $firstArgument;

                    endif;

                endif;

                // first argument is an object? then convert to an array
                if (is_object($firstArgument)) $firstArgument = func()->toArray($firstArgument);
                
                // first argument is a json data? 
                if (is_string($firstArgument) && trim($firstArgument[0]) == '{') :

                    // then convert to an array
                    $firstArgument = func()->toArray(json_decode($firstArgument));

                endif;

                // get query and binds if first argument is an array
                if (is_array($firstArgument)) :
                    
                    // get separator
                    $seperator = 'AND';

                    // check for separator within arguments
                    if (isset($arguments[1]) && $arguments[1] == 'OR') :
                    
                        // update separator
                        $seperator = 'OR';

                        // remove separator
                        unset($arguments[1]);

                    endif;

                    // use array bind method to get the bind parameters
                    $arrayBind = $this->__arrayBind($firstArgument, $seperator);

                    // find placeholder replacement
                    if (isset($this->placeholderReplacement['{column}'])) :

                        // replace table placeholder in statement 
                        $statement = str_replace('{column}', implode(',', $this->placeholderReplacement['{column}']), $statement);

                    endif;

                    // update statement
                    $statement = str_replace('{column}', '*', $statement);
                    
                    if (strlen($arrayBind['set']) > 1) $statement = str_replace('{where}', 'WHERE '.$arrayBind['set'].' ', $statement);

                    // update query and bind
                    $this->query = $statement;
                    $this->bind = $arrayBind['bind'];

                    if ($arrayBind['set'] == '') :

                        // check for where statement
                        $lastStatement = end($arguments);

                        // load where statement
                        if (is_array($lastStatement)) $this->where($lastStatement);

                    endif;

                    // remove array
                    array_shift($arguments);

                else:

                    if (preg_match('/(=|!=|>|<|>=|<=)/', $firstArgument)) :
                    
                        // get string bind
                        $stringBind = $this->__stringBind($firstArgument);
                        $bind = $stringBind['bind'];

                        // shift cursor
                        array_shift($arguments);

                        // add to binds
                        $this->__addBind($arguments, $bind);

                        // update statement
                        $statement = str_replace('{where}', 'WHERE '.$stringBind['line'].' ', $statement);
                        $statement = str_replace('{column}', '*', $statement);

                        // update query and bind
                        $this->query = $statement;
                        $this->bind = $bind;
                    
                    else :

                        // @var bool $continue
                        $continue = false;

                        if (preg_match('/[,]/', $firstArgument) || (isset($arguments[1]) && preg_match('/[=]/', $firstArgument)) || !isset($arguments[1])) :
                        
                            $continue = true;
                        
                        else:
                        
                            if (!isset($arguments[1])) :
                            
                                $continue = true;
                            
                            else:
                            
                                $stringBind = $this->__stringBind($firstArgument);
                                $bind = $stringBind['bind'];

                                // shift pointer
                                array_shift($arguments);

                                // add bind
                                $this->__addBind($arguments, $bind);

                                $statement = str_replace('{where}', 'WHERE '.$stringBind['line'].' ', $statement);
                                $statement = str_replace('{column}', '*', $statement);

                                // update query and bind
                                $this->query = $statement;
                                $this->bind = $bind;

                            endif;

                        endif;

                        if ($continue)
                        {
                            // update statement
                            $statement = str_replace('{column}', $firstArgument, $statement);

                            // update pointer for arguments
                            array_shift($arguments);

                            // continue if values resides in arguments
                            if (count($arguments) > 0) :
                            
                                // get first argument from new pointer
                                $firstArgument = $arguments[0];

                                // is object? convert to array
                                if (is_object($firstArgument)) $firstArgument = func()->toArray($firstArgument);

                                // json data? convert to array
                                if (is_string($firstArgument) && trim($firstArgument[0]) == '{' ) $firstArgument = func()->toArray(json_decode($firstArgument));

                                // continue with array
                                if (is_array($firstArgument)) :
                                
                                    $condition = 'AND';

                                    if (isset($arguments[1]) && $arguments[1] == 'OR') :
                                        
                                        // update condition
                                        $condition = 'OR';

                                        // remove condition;
                                        unset($arguments[1]);

                                    endif;

                                    // get array bind
                                    $arrayBind = $this->__arrayBind($firstArgument, $condition);

                                    // update statement
                                    $statement = str_replace('{column}', '*', $statement);
                                    $statement = str_replace('{where}', 'WHERE '.$arrayBind['set'].' ', $statement);

                                    // update query and bind
                                    $this->query = $statement;
                                    $this->bind = $arrayBind['bind'];
                                
                                else:
                                
                                    $stringBind = $this->__stringBind($firstArgument);
                                    $bind = $stringBind['bind'];

                                    array_shift($arguments);

                                    $this->__addBind($arguments, $bind);

                                    $statement = str_replace('{where}', 'WHERE '.$stringBind['line'].' ', $statement);
                                    $statement = str_replace('{column}', '*', $statement);

                                    $this->query = $statement;
                                    $this->bind = $bind;

                                endif;

                            endif;
                        }

                        $this->query = $statement;

                    endif;
                
                endif;


            else :

                // update statement
                $statement = str_replace('{column}', '*', $statement);

                // update query
                $this->query = $statement;

            endif;

            // cache now
            $mixedData !== null ? $mixedData($this->query, $this->bind) : null;

        else:

            // use from cache
            $this->query = $mixedData['query'];
            $this->bind = $mixedData['bind'];

            // clean up
            unset($statement);

        endif;

        // clean up
        $hashName = null;

        // run callback closure
        $this->runStatementClosure($CallbackClosure);

        return $this;
    }
}