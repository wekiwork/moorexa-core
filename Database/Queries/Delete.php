<?php
namespace Lightroom\Database\Queries;

use Lightroom\Database\QueryBuilder;
use Lightroom\Database\Cache\QueryCache;

/**
 * @package QueryBuilder Delete Query
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is the default delete builder. Can be replaced whenever
 */
trait Delete 
{
    /**
     * @method Delete deleteStatement
     * @param array $arguments
     * @param string  $statement (like a markup that contains placeholders like '{where}', '{table}')
     * @return QueryBuilder
     */
    public function deleteStatement(array $arguments, string $statement)
    {
        // run callback
        $CallbackClosure = $this->findCallbackFromArguments($arguments);

        // set mixedData
        $mixedData = null;

        // check if not hashed
        if (QueryCache::isCached('delete', func_get_args(), $mixedData) === false) :
            
            if (count($arguments) > 0) :
            
                // first argument passed
                $firstArgument = $arguments[0];

                // get rules data for object passed.
                if (is_object($firstArgument)) :
                
                    if (method_exists($firstArgument, 'rulesHasData')) :
                    
                        // get return
                        $firstArgument = $firstArgument->rulesHasData();

                        // array shift
                        $arguments[0] = $firstArgument;

                    endif;

                endif;

                // is object? convert to array
                if (is_object($firstArgument)) $firstArgument = func()->toArray($firstArgument);

                // json data? convert to array
                if (is_string($firstArgument) && trim($firstArgument[0]) == '{' ) $firstArgument = func()->toArray(json_decode($firstArgument));

                // continue with array
                if (is_array($firstArgument)) :
                
                    $arrayBind = $this->__arrayBind($firstArgument, 'OR');

                    // update statement
                    $statement = str_replace('{where}', 'WHERE '.$arrayBind['set'].' ', $statement);

                    // update query and bind
                    $this->query = $statement;
                    $this->bind = $arrayBind['bind'];

                    // update cursor
                    array_shift($arguments);
                
                else:
                    
                    // has assignment
                    if (preg_match('/(=|!=|>|<|>=|<=)/', $firstArgument)) :
                    
                        $stringBind = $this->__stringBind($firstArgument);
                        $bind = $stringBind['bind'];

                        // update cursor
                        array_shift($arguments);

                        $this->__addBind($arguments, $bind);

                        // update statement
                        $statement = str_replace('{where}', 'WHERE '.$stringBind['line'].' ', $statement);

                        // update query and bind
                        $this->query = $statement;
                        $this->bind = $bind;
                    
                    else:
                    
                        $continue = false;

                        if (preg_match('/[,]/', $firstArgument) || (isset($arguments[1]) && preg_match('/[=]/', $firstArgument)) || !isset($arguments[1])) :
                        
                            $continue = true;
                        
                        else:
                        
                            if (!isset($a[1])):
                            
                                $continue = true;
                            
                            else:
                            
                                $stringBind = $this->__stringBind($firstArgument);
                                $bind = $stringBind['bind'];

                                // update cursor
                                array_shift($arguments);

                                // update bind
                                $this->__addBind($arguments, $bind);

                                // update statement
                                $statement = str_replace('{where}', 'WHERE '.$stringBind['line'].' ', $statement);

                                // update query and bind
                                $this->query = $statement;
                                $this->bind = $bind;

                            endif;

                        endif;

                        // continue
                        if ($continue) :
                        
                            // update cursor
                            array_shift($arguments);

                            if (count($arguments) > 0) :
                            
                                $firstArgument = $arguments[0];

                                // is object? convert to array
                                if (is_object($firstArgument)) $firstArgument = func()->toArray($firstArgument);

                                // json data? convert to array
                                if (is_string($firstArgument) && trim($firstArgument[0]) == '{' ) $firstArgument = func()->toArray(json_decode($firstArgument));

                                // convert to array
                                if (is_array($firstArgument)) :
                                
                                    $arrayBind = $this->__arrayBind($firstArgument, 'OR');

                                    $statement = str_replace('{where}', 'WHERE '.$arrayBind['set'].' ', $statement);

                                    // update query and bind
                                    $this->query = $statement;
                                    $this->bind = $arrayBind['bind'];
                                
                                else:
                                
                                    $stringBind = $this->__stringBind($firstArgument);
                                    $bind = $stringBind['bind'];

                                    // update cursor
                                    array_shift($arguments);

                                    // add binds
                                    $this->__addBind($arguments, $bind);

                                    // update statement
                                    $statement = str_replace('{where}', 'WHERE '.$stringBind['line'].' ', $statement);

                                    // update query and bind
                                    $this->query = $statement;
                                    $this->bind = $bind;

                                endif;
                            
                            else:
                            
                                $stringBind = $this->__stringBind($firstArgument);
                                $bind = $stringBind['bind'];

                                // update pointer
                                array_shift($arguments);

                                // add bind
                                $this->__addBind($arguments, $bind);

                                // update statement
                                $statement = str_replace('{where}', 'WHERE '.$stringBind['line'].' ', $statement);

                                // update query and bind
                                $this->query = $statement;
                                $this->bind = $bind;

                            endif;

                        endif;
                    endif;
                endif;

                // update query
                $this->query = $statement;
            
            else:
                // update query
                $this->query = $statement;
            endif;  

            // save to cache
            $mixedData !== null ? $mixedData($this->query, $this->bind) : null;

        else:

            // use from cache
            $this->query = $mixedData['query'];
            $this->bind = $mixedData['bind'];

        endif;

        // run callback closure
        $this->runStatementClosure($CallbackClosure);

        // return QueryBuilderInterface
        return $this;
    }
}