<?php 
namespace Lightroom\Database;

/**
 * @package Query Builder Magic Methods
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 * 
 * This contains all the magic methods for the default query builder
 */
trait QueryBuilderMagicMethods
{
    use QueryBindMethods;
    
    /**
     * @method QueryBuilder __arrayInsertBody
     * @param array $array 
     * @param array $statement
     * @return array
     * 
     * This builds an array of values for insertStatement
     */
    private function __arrayInsertBody(array $array, array $statement) : array
    {
        static $staticIndex = 0;
        
        // @var array $values
        $values = [];

        // @var array $binds
        $binds = [];

        // using foreach to get array values
        foreach ($array as $arrayValue) :
            
            // convert object to array
            if (is_object($arrayValue)) $arrayValue = func()->toArray($arrayValue);

            // proceed with array
            if (is_array($arrayValue)) :
            
                // @var int $localIndex
                $localIndex = 0;
                // @var array $value
                $value = [];

                foreach ($arrayValue as $val) :
                    
                    // @var string $headerKey
                    $headerKey = trim($statement[$localIndex]);

                    // add to value
                    $value[] = ':'.$headerKey.$staticIndex;

                    // @var mixed $headerValue
                    $headerValue = isset($arrayValue[$headerKey]) ? $arrayValue[$headerKey] : (isset($arrayValue[$localIndex]) ? $arrayValue[$localIndex] : null);

                    if (is_string($headerValue)) :
                    
                        // decode header value
                        $headerValue = html_entity_decode($headerValue);

                        // add slashes if allowed
                        if (!$this->allowSlashes) $headerValue = addslashes(stripslashes($headerValue));
                    
                    endif;

                    // @var string $cleanIndex
                    $cleanIndex = $this->cleanBind($headerKey).$staticIndex;

                    // manage closure 
                    if (!is_string($headerValue) && is_callable($headerValue)) :

                        // remove the last
                        array_pop($value);

                        // get value
                        $value[] = '' . $this->inject($headerValue, '', $binds) . '';

                    else:

                        // add to binds
                        $binds[$cleanIndex] = $headerValue;

                    endif;

                    //increment local index
                    $localIndex++;

                endforeach;

                // update values
                $values[] = '('.implode(',', $value).')';

                // increment static index
                $staticIndex++;

            endif;

        endforeach;

        // reset pointer
        $staticIndex = 0;

        // return array
        return ['values' => implode(',', $values), 'bind' => $binds];
    }

    /**
     * @method QueryBuilder __arrayInsertHeader
     * @param array $array
     * @return array
     * 
     * Takes an array and returns a table header and describes the structure of that header in an array
     * It is used by the insertStatement.
     */
    private function __arrayInsertHeader(array $array) : array
    {
        // @var array $header
        $header = [];

        // check if first argument is an array
        if (isset($array[0]) && is_array($array[0])) :
            
            // get the key and value
            foreach ($array[0] as $key => $value) :
            
                if (is_string($key)) :
                    // set key as value if key is a string
                    $header[] = $key;
                else:
                    // set value as key if $key is numeric
                    $header[] = $value;
                endif;
            
            endforeach;
        
        endif;

        // return array
        return ['header' => implode(',', $header), 'structure' => $header];
    }

    /**
     * @method QueryBuilder __avoidClashes
     * @param array &$bind
     * @param array &$newBind
     * @return mixed
     * Avoids clashes between two binds.
     */
    private function __avoidClashes(array &$bind, array &$newBind)
    {
        static $index = 0; // static index
        // @var array $currentBind
        $currentBind = $this->bind;
        // @var bool $added (after a check, if value hasn't been added we continue, else we update this variable)
        $added = false;
        // @var int $return (return value)
        $return = 0;

        foreach($bind as $key => $value) :
        
            // avoid name clashes..
            if (isset($currentBind[$key])) :
            
                if (empty($currentBind[$key])) :
                    
                    // add to new bind
                    $this->decodeAddSlashesAndBind($key, $value, $newBind);
                else:
                
                    $return = $index; // from static index

                    // add to new bind
                    $this->decodeAddSlashesAndBind(($key.$index), $value, $newBind);

                    $index++; // increment static index

                    // update added
                    $added = true;
                endif;
            
            else:
                // add to new bind
                $this->decodeAddSlashesAndBind($key, $value, $newBind);
            endif;

        endforeach;

        // if added, return index
        if ($added) return $return;

        // return an empty string
        return '';

    }

    /**
     * @method QueryBuilder runBinding
     * @param array $arguments
     * @return QueryBuilder
     *
     * This method takes a list of arguments, builds a bind for PDO prepared statements.
     */
    private function runBinding(...$arguments)
    {
        // set binds from arguments if $arguments count is one with an array as its first index.
        if (count($arguments) == 1 && is_array($arguments[0])) $this->bind = array_merge($this->bind, $arguments[0]);
  
        // continue if bind is greater than one
        if (count($this->bind) > 0) :
        
            // @var array $__bind
            $__bind = [];

            // create empty value
            foreach ($this->bind as $key => $val) :
            
                // create empty value
                if (empty($val)) $__bind[$this->cleanBind($key)] = '';

            endforeach;

            // continue if $__bind is greater than zero
            if (count($__bind) > 0) :
            
                // @var int $index
                $index = 0;

                // @var array $bind
                $bind = [];

                if (is_array($arguments[0])) :
                    
                    // get key and value
                    foreach ($arguments[0] as $key => $value) :
                        
                        // clean bind
                        $key = $this->cleanBind($key);

                        // key is string and presently exits in bind
                        if (is_string($key) && isset($__bind[$key])) :
                        
                            // update with value
                            $bind[$key] = $value;
                        
                        else:
                            // @var array $keys
                            $keys = array_keys($__bind);

                            // check if $key exists
                            if (isset($keys[$key])) :
                                
                                // get key from keys
                                $keyFromKeys = $keys[$key];

                                // update value
                                $bind[$keyFromKeys] = $value;

                            endif;

                        endif;

                    endforeach;
                
                else:
                    
                    // get key and value
                    foreach ($__bind as $key => $val) :
                        
                        // clean bind 
                        $this->cleanBind($key);

                        if (isset($arguments[$index])) :
                        
                            if (is_string($arguments[$index])) :
                                
                                // get value 
                                $value = $arguments[$index];

                                // if html is not allowed then remove tags
                                if (!$this->allowHTMLTags) $value = strip_tags($value);

                                // if slashes is allowed then add slashes
                                if ($this->allowSlashes) $value = addslashes($value);

                                // update bind
                                $bind[$key] = $value;
                            
                            elseif (is_object($arguments[$index]) || is_array($arguments[$index])):
                                
                                // @var string $command
                                $command = $this->method;

                                // log errors
                                if (is_array($this->errorPack[$command])) :
                                
                                    $this->errorPack[$command][] = 'Invalid Bind parameter. Scaler Type expected, Compound Type passed.';
                                
                                else:
                                
                                    $this->errorPack[$command] = [];
                                    $this->errorPack[$command][] = 'Invalid Bind parameter. Scaler Type expected, Compound Type passed.';
                                endif;
                            
                            else:
                                
                                // update bind with value
                                if (isset($arguments[$index])) $bind[$key] = $arguments[$index];

                            endif;
                        
                        else: 
                            // update bind with value or empty string
                            $bind[$key] = isset($arguments[$index-1]) ? $arguments[$index-1] : '';
                        endif;  

                        // update local index
                        $index++;

                    endforeach;

                endif;

                // @var array $newBind
                $newBind = [];
                $this->__avoidClashes($bind, $newBind);

                // update bind
                $this->bind = array_merge($this->bind, $newBind);

            endif;

        endif;

        // return QueryBuilder
        return $this;
    }

    /**
     * @method QueryBuilder runWhere
     * @param array $arguments
     * @return QueryBuilder
     *
     * This method takes a list of arguments, builds a where statement for PDO prepared statements.
     */
    private function runWhere(...$arguments)
    {
        // continue if arguments has at least one argument
        if (count($arguments) > 0) :
        
            // get statement
            $statement = $this->query;

            // get first argument
            $firstArgument = $arguments[0];

            // continue with this block if first argument is an array, object or json data
            if (is_array($firstArgument) || is_object($firstArgument) || (is_string($firstArgument) && $firstArgument[0] == '{')) :
            
                // convert object to array
                if (is_object($firstArgument)) $firstArgument = func()->toArray($firstArgument);

                // convert json string to array
                if (is_string($firstArgument) && $firstArgument[0] == '{') $firstArgument = func()->toArray(json_decode($firstArgument));

                // continue with array
                if (is_array($firstArgument)) :
    
                    // @var string $separator
                    $separator = isset($arguments[1]) ? $arguments[1] : 'and';

                    // get where and bind
                    $whereBind = $this->__arrayBind($firstArgument, $separator);
                    $where = $whereBind['set'];
                    $bind = $whereBind['bind'];

                    $this->buildQueryAndLastWhere($statement, $where);

                    $newBind = [];

                    // avoid clashes
                    $this->__avoidClashes($bind, $newBind);

                    // update binds
                    $this->bind = array_merge($this->bind, $newBind);
                
                else:
                    $this->errorPack[$this->method][] = 'Where statement not valid. Must be a string, object, array or json string';
                endif;
            
            else:
            
                // continue if first argument is a string
                if (is_string($firstArgument)):

                    // get second argument
                    $secondArgument = isset($arguments[1]) ? $arguments[1] : null;

                    // @var array $bind
                    $bind = [];

                    // @var string $where 
                    $where = '';

                    // handle callback function
                    if ($secondArgument !== null && !is_string($secondArgument) && is_callable($secondArgument)) :

                        // remove second argument
                        unset($arguments[1]);

                        // inject closure
                        $where .=  $firstArgument . ' ' . $this->inject($secondArgument, '', $bind);

                    else:

                        // using string bind method
                        $stringBind = $this->__stringBind($firstArgument);

                        // get where and bind
                        $where = $stringBind['line'];
                        $bind = $stringBind['bind'];

                    endif;
                    
                    // bind query
                    $this->buildQueryAndLastWhere($statement, $where);

                    // remove the first argument
                    array_shift($arguments);

                    // get binds
                    $this->__addBind($arguments, $bind);

                    // @var array $newBind
                    $newBind = [];

                    // avoid clashes
                    $this->__avoidClashes($bind, $newBind);

                    // update bind
                    $this->bind = array_merge($this->bind, $newBind);

                endif;

            endif;

            // clean up
            $firstArgument = null;

        endif;

        return $this;
    }

    /**
     * @method QueryBuilder cleanBind
     * @param string $key
     * @return string
     */
    private function cleanBind(string $key) : string 
    {
        return preg_replace('/[^a-zA-Z_0-9]/', '', $key);
    }
}