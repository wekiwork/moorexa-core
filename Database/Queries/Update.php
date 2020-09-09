<?php
namespace Lightroom\Database\Queries;

use Lightroom\Database\QueryBuilder;
use Lightroom\Database\Cache\QueryCache;

/**
 * @package QueryBuilder Update Query
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is the default update builder. Can be replaced whenever
 */
trait Update 
{
    /**
     * @method Update updateStatement
     * @param array $arguments
     * @param string  $statement (like a markup that contains placeholders like '{where}', '{table}')
     * @return QueryBuilder
     */
    public function updateStatement(array $arguments, string $statement)
    {
        // run callback
        $CallbackClosure = $this->findCallbackFromArguments($arguments);

        // continue if values are present
        if (count($arguments) > 0) :
            
            // set mixedData
            $mixedData = null;
            
            // check if not hashed
            if (QueryCache::isCached('update', func_get_args(), $mixedData) === false) :

                // check if args 2 is string and possibly an object
                if (isset($arguments[1]) && is_string($arguments[1])) :
                
                    $object = json_decode($arguments[1]);
                    $copy = $arguments;

                    // build new args
                    $newArgs = [];

                    if (is_string($arguments[0])) :
                    
                        if (is_null(json_decode($arguments[0]))) :
                        
                            // get columns  
                            $columns = explode(',', $arguments[0]);

                            foreach ($object as $key => $val) :
                            
                                $row = [];

                                $row[trim($columns[0])] = $key;
                                $row[trim($columns[1])] = $val;

                                $newArgs[] = $row;

                            endforeach;

                            if (count($newArgs) > 0) :
                            
                                unset($arguments[0], $arguments[1]);

                                // update arguments
                                $arguments = array_merge($newArgs, $arguments);

                            endif;

                        endif;

                    endif;

                endif;

                // first argument passed
                $firstArgument = $arguments[0];

                // get rules data for object passed.
                if (is_object($firstArgument)) :
                
                    if (method_exists($firstArgument, 'rulesHasData')) :
                        
                        // get rule data from input rules class
                        $firstArgument = $firstArgument->rulesHasData();

                        // array shift
                        $arguments[0] = $firstArgument;

                    endif;

                endif;

                // is object? convert to array
                if (is_object($firstArgument)) $firstArgument = func()->toArray($firstArgument);

                // json data?
                if (is_string($firstArgument) && trim($firstArgument[0]) == '{' ) :
                
                    // convert to an object
                    $firstArgument = func()->toArray(json_decode($firstArgument));
                    $arguments[0] = $firstArgument;

                endif;


                // data passed is an array
                if (is_array($firstArgument)) :
                
                    $arrayBind = $this->__arrayBind($firstArgument);

                    // update statement
                    $statement = str_replace('{query}', $arrayBind['set'], $statement);

                    // update query and bind
                    $this->query = $statement;
                    $this->bind = $arrayBind['bind'];

                    // update cursor
                    array_shift($arguments);
                
                else:
                
                    // get the string bind
                    $stringBind = $this->__stringBind($firstArgument);
                    $bind = $stringBind['bind'];

                    // update cursor
                    array_shift($arguments);

                    // add binds
                    $this->__addBind($arguments, $bind);

                    // update statement
                    $statement = str_replace('{query}', $stringBind['line'], $statement);

                    // update query and bin
                    $this->query = $statement;
                    $this->bind = $bind;

                endif;

                // where added ?
                if (count($arguments) > 0) :
                
                    if (is_array($arguments[0]) || is_object($arguments[0]) || (is_string($arguments[0]) && $arguments[0] == '{')) :
                    
                        // convert to array
                        if (is_object($arguments[0])) $arguments[0] = func()->toArray($arguments[0]);

                        // convert string to array
                        if (is_string($arguments[0]) && $arguments[0] == '{') $arguments[0] = func()->toArray(json_decode($arguments[0]));


                        if (is_array($arguments[0])) :
                        
                            $whereBind = $this->__arrayBind($arguments[0], 'AND');
                            $where = $whereBind['set'];
                            $bind = $whereBind['bind'];

                            // update statement
                            $statement = str_replace('{where}', 'WHERE '.$where.' ', $statement);

                            // update query and lastWhere
                            $this->query = $statement;
                            $this->lastWhere = 'WHERE '.$where.' ';

                            $newBind = [];

                            // avoid clashes
                            $this->__avoidClashes($bind, $newBind);

                            // update bind
                            $this->bind = array_merge($this->bind, $newBind);

                        else:
                        
                            $this->errorPack['update'][] = 'Where statement not valid. Must be a string, object, array or json string';
                            
                        endif;
                
                    else:
                    
                        if (is_string($arguments[0])) :
                        
                            $stringBind = $this->__stringBind($arguments[0]);
                            $where = $stringBind['line'];
                            $bind = $stringBind['bind'];    

                            // update statement
                            $statement = str_replace('{where}', 'WHERE '.$where.' ', $statement);

                            // update query and last where
                            $this->query = $statement;
                            $this->lastWhere = 'WHERE '.$where.' ';

                            // update cursor
                            array_shift($arguments);

                            // add bind
                            $this->__addBind($arguments, $bind);

                            $newBind = [];

                            // avoid clashes
                            $this->__avoidClashes($bind, $newBind);

                            // update bind
                            $this->bind = array_merge($this->bind, $newBind);

                        endif;

                    endif;

                endif;

                // cache now
                $mixedData !== null ? $mixedData($this->query, $this->bind) : null;

            else:
                // use from cache
                $this->query = $mixedData['query'];
                $this->bind = $mixedData['bind'];

            endif;
            
        else:
        
            // error, no data passed
            $this->errorPack['update'][] = 'No data passed.';

        endif;

        // run callback closure
        $this->runStatementClosure($CallbackClosure);

        // return QueryBuilderInterface
        return $this;
    }
}