<?php
namespace Lightroom\Database;

/**
 * @package QueryBindMethods
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait QueryBindMethods
{
    private static $stringBindStatements = [];

    // allow slashes
    public $allowSlashes = false;

    /**
     * @method QueryBuilder __stringInsertBind
     * @param string $statement
     * @return array
     * 
     * This method gets all raw values from an sql statement and convert them to prepared statements for insert queries.
     */
    private function __stringInsertBind(string $statement) : array
    {
        if (!isset(self::$stringBindStatements[$statement])) :

            // create copy
            $statementCopy = $statement;

            // get all strings
            preg_match_all('/[\'|"]([\s\S])[\'|"|\S]+[\'|"]/', $statement, $match);

            // @var array $string
            $strings = [];

            if (count($match[0]) > 0) :
            
                foreach($match[0] as $index => $string) :
                
                    $strings[] = $string;
                    $statement = str_replace($string, ':string'.$index, $statement);

                endforeach;

            endif;

            // now split by comma
            $splitArray = explode(',', $statement);

            // replace strings now with original values.
            if (count($strings) > 0) :
                
                // update split array
                foreach($strings as $index => $string) $splitArray[$index] = str_replace(':string'.$index, $string, $splitArray[$index]);

            endif;

            $bind = [];
            $header = [];
            $values = [];

            // check if we don't have lvalue and rvalue
            static $staticIndex = 0;

            foreach($splitArray as $index => $splitValue) :
            
                // remove white spaces
                $splitValue = trim($splitValue);

                if (preg_match('/[=]/', $splitValue)) :
                
                    // get right value
                    $position = strpos($splitValue, '=');
                    $rightVal = trim(substr($splitValue, $position+1));

                    // get left value
                    $leftVal = $this->cleanBind(trim(substr($splitValue, 0, $position)));

                    // update header
                    if (!in_array($leftVal, $header)) $header[] = $leftVal;
        

                    if ($rightVal == '?') :
                    
                        // update values and bin
                        $values[] = ':'.$leftVal.$staticIndex;

                        $rightVal = ':'.$leftVal;
                        $bind[$leftVal.$staticIndex] = '';
                    
                    elseif ($rightVal[0] == ':') :
                        
                        // update values and bind
                        $values[] = $rightVal.$staticIndex;
                        $bind[$rightVal.$staticIndex] = '';
                    
                    else:
                    
                        // has values
                        $start = $rightVal[0];

                        if (preg_match("/[a-zA-Z0-9|'|\"]/", $start)) :
                        
                            if ($start == '"') :
                            
                                $rightValCopy = $rightVal;
                                $positionEnd = strrpos($rightVal, '"');
                                $rightVal = substr($rightVal, 0, $positionEnd+1);

                                // update split value
                                $splitValue = str_replace($rightValCopy, $rightVal, $splitValue);
                                $splitArray[$index] = $splitValue;
                            
                            elseif ($start == "'") :
                            
                                $rightValCopy = $rightVal;
                                $positionEnd = strrpos($rightVal, "'");
                                $rightVal = substr($rightVal, 0, $positionEnd+1);

                                // update split array
                                $splitValue = str_replace($rightValCopy, $rightVal, $splitValue);
                                $splitArray[$index] = $splitValue;
                            
                            elseif (preg_match('/^[0-9]/', $start)) :
                            
                                $rightValCopy = $rightVal;
                                $positionEnd = strpos($rightVal,' ');

                                if ($positionEnd !== false) :
                                
                                    $rightVal = substr($rightVal, 0, $positionEnd);

                                    // update split array
                                    $splitValue = str_replace($rightValCopy, $rightVal, $splitValue);
                                    $splitArray[$index] = $splitValue;

                                endif;

                            endif;

                        endif;

                        $rightVal = preg_replace('/^[\'|"]/','',$rightVal);
                        $rightVal = preg_replace('/[\'|"]$/','',$rightVal);
                        $rightVal = html_entity_decode($rightVal);

                        if (!$this->allowSlashes) $rightVal = addslashes(stripslashes($rightVal));            

                        // update binds
                        $values[] = ':'.$leftVal.$staticIndex;
                        $bind[$leftVal.$staticIndex] = $rightVal;

                    endif;
                
                endif;

                // increment static index
                $staticIndex++;

            endforeach;
            
            // reset static index
            $staticIndex = 0;

            // save now
            self::$stringBindStatements[$statementCopy] = [
                'values' => $values,
                'bind' => $bind,
                'header' => $header
            ];

        else :

            // return cache
            return self::$stringBindStatements[$statement];

        endif;

        // return array
        return ['values' => $values, 'bind' => $bind, 'header' => $header];
    }
    
    /**
     * @method QueryBuilder __stringBind
     * @param string $statement
     * @param mixed $leftValue
     * @param mixed $rightValue
     * @return array
     * 
     * This method gets all raw values from an sql statement and convert them to prepared statements.
     */
    private function __stringBind(string $statement, $leftValue = null, $rightValue = null) : array
    {
        if (!isset(self::$stringBindStatements[$statement])) :

            // statement copy
            $statementCopy = $statement;

            // get all values
            preg_match_all('/[\'|"]([\s\S])[\'|"|\S]+[\'|"]/',$statement, $match);

            // @var array $values
            $values = [];

            // get index from match array
            if (count($match[0]) > 0) :
        
                foreach($match[0] as $index => $value) :
                    
                    // add to values
                    $values[] = $value;

                    // update statement
                    $statement = str_replace($value, ':string'.$index, $statement);

                endforeach;

            endif;

            // now split by comma, or, and
            $splitArray = preg_split('/(\s+or\s+|\s+OR\s+|\s+and\s+|[,]|\s+AND\s+)/', $statement);

            // watch out for other valid sql keywords.
            foreach($splitArray as $index => $splitValue) :
            
                // trim off white spaces.
                $splitValue = trim($splitValue);

                // find assignment
                if (!preg_match('/[=]/',$splitValue) || preg_match('/^[0-9]/',$splitValue)) :
                
                    // move cursor backward with 1
                    if (isset($splitArray[$index-1])) :

                        // LIMIT keyword used ?
                        if (stripos($splitArray[$index-1], 'limit')) :
                        
                            // skip assignment
                            $splitArray[$index-1] .= ','.$splitValue;

                            // remove index
                            unset($splitArray[$index]);

                            // sort split array
                            sort($splitArray);

                        endif;

                    endif;

                endif;

            endforeach;

            // replace values now with original value.
            if (count($values) > 0) :
                
                foreach($values as $index => $value) :
                
                    // update split array
                    $splitArray[$index] = str_replace(':string'.$index, $value, $splitArray[$index]);

                    // update statement
                    $statement = str_replace(':string'.$index, $value, $statement);

                endforeach;

            endif;

            // @var array $bind
            $bind = [];
            // @var array $newSplit
            $newSplit = [];

            // check if we don't have lvalue and rvalue
            static $staticIndex = 0;

            // update split array
            foreach($splitArray as $index => $splitValue) :
        
                // trim of white spaces from split value
                $splitValue = trim($splitValue);

                if (!preg_match('/(=|!=|>|<|>=|<=)/', $splitValue)) :
                
                    $query = implode(',', $newSplit);
                    $splitValueCopy = $splitValue;

                    if (preg_match("/[:]($splitValue)/", $this->query) || preg_match("/[:]($splitValue)/", $query)) :
                    
                        $splitValue .= $staticIndex;
                        $bind[$this->cleanBind($splitValue)] = '';

                        $staticIndex++;
                    
                    else:
                    
                        $bind[$splitValue] = '';
                    endif;

                    // update left and right value
                    $leftValue = is_null($leftValue) ? ' = ' : $leftValue;
                    $rightValue = is_null($rightValue) ? '' : $rightValue;

                    // update new value
                    $newValue = $splitValueCopy . $leftValue . ':' . $this->cleanBind($splitValue) . $rightValue;
                    $splitValue = $newValue;
                
                else:

                    // get rvalue
                    $position = strpos($splitValue, '=');
                    $separator = '=';

                    if ($position===false) :
                    
                        if (preg_match('/(!=)/',$splitValue)) :
                        
                            $position = strpos($splitValue, '!=');
                            $separator = '!=';

                        endif;

                    endif;

                    if ($position===false) :
                    
                        if (preg_match('/(>)/', $splitValue)) :
                        
                            $position = strpos($splitValue, '>');
                            $separator = '>';

                        endif;

                    endif;

                    if ($position===false) :
                    
                        if (preg_match('/(<)/',$splitValue)) :
                        
                            $position = strpos($splitValue, '<');
                            $separator = '<';

                        endif;

                    endif;

                    if ($position===false) :
                    
                        if (preg_match('/(>=)/',$splitValue)) :
                        
                            $position = strpos($splitValue, '>=');
                            $separator = '>=';

                        endif;

                    endif;

                    if ($position===false) :
                    
                        if (preg_match('/(<=)/',$splitValue)) :
                        
                            $position = strpos($splitValue, '<=');
                            $separator = '<=';

                        endif;

                    endif;

                    $rightVal = trim(substr($splitValue, $position+intval(strlen($separator))));

                    // get lvalue
                    $leftVal = trim(substr($splitValue, 0, $position));
                    $leftVal = trim(preg_replace('/[!|=|<|>]$/','',$leftVal));


                    if ($rightVal == '?') :
                    
                        static $staticIndex2 = 0;

                        $query = implode(',', $newSplit);

                        if (preg_match("/[:]($leftVal)/", $this->query) || preg_match("/[:]($leftVal)/", $query)) :
                        
                            $leftVal .= $staticIndex2;
                            $staticIndex2++;

                        endif;

                        // update bind and split value
                        $rightVal = ':'.$this->cleanBind($leftVal);
                        $splitValue = str_replace('?', $rightVal, $splitValue);
                        $bind[$this->cleanBind($leftVal)] = '';
                    
                    elseif ($rightVal[0] == ':') :
                    
                        static $staticIndex3 = 0;

                        $query = implode(',', $splitArray);

                        if (preg_match("/($rightVal)/", $this->query) || preg_match("/[:]($leftVal)/", $query)) :
                        
                            $bind[$this->cleanBind(substr($rightVal,1)).$staticIndex3] = '';
                            $staticIndex3++;
                        
                        else:
                        
                            $bind[$this->cleanBind(substr($rightVal,1))] = '';
                        endif;

                    
                    else:
                    
                        static $staticIndex4 = 0;

                        // has values
                        $start = $rightVal[0];

                        if (preg_match("/[a-zA-Z0-9|'|\"]/", $start)) :
                        
                            if ($start == '"') :
                            
                                $rightValCopy = $rightVal;
                                $positionEnd = strrpos($rightVal, '"');
                                $rightVal = substr($rightVal, 0, $positionEnd+1);

                                // update split value
                                $splitValue = str_replace($rightValCopy, $rightVal, $splitValue);
                                $splitArray[$index] = $splitValue;
                            
                            elseif ($start == "'") :
                            
                                $rightValCopy = $rightVal;
                                $positionEnd = strrpos($rightVal, "'");
                                $rightVal = substr($rightVal, 0, $positionEnd+1);

                                // update split array
                                $splitValue = str_replace($rightValCopy, $rightVal, $splitValue);
                                $splitArray[$index] = $splitValue;
                            
                            elseif (preg_match('/^[0-9]/', $start)) :
                            
                                $rightValCopy = $rightVal;
                                $positionEnd = strpos($rightVal,' ');

                                if ($positionEnd !== false) :
                                
                                    $rightVal = substr($rightVal, 0, $positionEnd);

                                    // update split array
                                    $splitValue = str_replace($rightValCopy, $rightVal, $splitValue);
                                    $splitArray[$index] = $splitValue;

                                endif;

                            endif;

                        endif;

                        $rightVal = preg_replace('/^[\'|"]/','',$rightVal);
                        $rightVal = preg_replace('/[\'|"]$/','',$rightVal);
                        $rightVal = html_entity_decode($rightVal);

                        if (!$this->allowSlashes) $rightVal = addslashes(stripslashes($rightVal));     

                        $query = implode(', ', $newSplit);

                        if (preg_match("/[:]($leftVal)/", $this->query) || preg_match("/[:]($leftVal)/", $query)) :
                        
                            $splitValue = $leftVal .' '.$separator.' :' .$this->cleanBind($leftVal). $staticIndex4;
                            
                            $bind[$this->cleanBind($leftVal) . $staticIndex4] = $rightVal;

                            // update static index
                            $staticIndex4++;
                        
                        else :
                        
                            $splitValue = $leftVal .' '.$separator.' :'.$this->cleanBind($leftVal);

                            // update bind
                            $bind[$this->cleanBind($leftVal)] = $rightVal;

                        endif;

                    endif;

                endif;

                $newSplit[] = $splitValue;

            endforeach;

            // update $staticIndex
            $staticIndex = 0;

            // update statement
            if (is_string($statement)) :
                
                // @var array $originalData
                $originalData = [];

                foreach ($splitArray as $index => $value) :
                    
                    // get value position and value size
                    $position = strpos($statement, $value);
                    $valueSize = strlen($value);

                    // @var string $with
                    $with = "{".$position.$valueSize.substr(md5($value),0,mt_rand(10,40))."}";
                    
                    // update original data
                    $originalData[$with] = $newSplit[$index];

                    // update statement
                    $statement = substr_replace($statement, $with, $position, $valueSize);

                    // remove from split array
                    unset($splitArray[$index]);

                endforeach;

                // update statement
                foreach($originalData as $key => $value) $statement = str_replace($key, $value, $statement);
            
            else:
            
                $this->failed = true;
                $this->errorPack[$this->method][] = 'Empty string passed';

            endif;

            // save now
            self::$stringBindStatements[$statementCopy] = ['line' => $statement, 'bind' => $bind];

        else :

            // get statement
            $bindStatement = self::$stringBindStatements[$statement];

            // get statement and bind
            $statement = $bindStatement['line'];
            $bind = $bindStatement['bind'];

        endif;

        // return array
        return ['line' => $statement, 'bind' => $bind];
    }

    /**
     * @method QueryBuilder __addBind
     * @param array &$arguments
     * @param array &$bind
     * @return void
     * 
     * This methods add binds silently from an array of arguments
     */
    private function __addBind(array &$arguments, array &$bind) : void
    {
        // run if arguments has contents
        if (count($arguments) > 0) :
        
            // @var int $index
            $index = 0;

            // run through the bind array
            foreach ($bind as $bindKey => $bindValue) :
            
                if (empty($bindValue) && isset($arguments[$index])) :
                
                    // add to new bind
                    $this->decodeAddSlashesAndBind($bindKey, $arguments[$index], $bind);

                    // remove from arguments
                    unset($arguments[$index]);
                
                endif;

                // update index
                $index++;

            endforeach;

        endif;
    }

    /**
     * @method QueryBuilder __arrayBind
     * @param array $arguments
     * @param string $separator
     * @return array
     * 
     * Takes an array of arguments and returns a statement, bind keys and values
     */
    private function __arrayBind(array $arguments, string $separator = ',') : array
    {
        // @var string $statement
        $statement = '';

        // @var array $bind
        $bind = [];

        // @var array placeholderReplacement
        $placeholderReplacement = [];

        // get key and value from argument
        foreach ($arguments as $key => $value) :
            
            // check for closure function
            if ($value !== null && !is_string($value) && is_callable($value)) :

                // update statement
                if (is_string($key)) : 

                    // inject closure function
                    $value = $this->inject($value, '', $bind);

                    $statement .= $key.' = '.$value.' '.$separator.' '; 

                else:

                    // inject closure function
                    $value = $this->inject($value, '', $bind);
                    
                    // add for replacement
                    $placeholderReplacement['{column}'][] = $value;
                
                endif;

            // $value is not an object or array
            elseif (!is_array($value) and !is_object($value)) :
            
                // update statement
                if (is_string($key)) : 
                    
                    $statement .= $key.' = :'.$this->cleanBind($key).' '.$separator.' '; 

                    // decode value
                    $value = html_entity_decode($value);

                    // if slashes are not allowed, then add slashes
                    if (!$this->allowSlashes) $value = addslashes(stripslashes($value));

                    // update bind array
                    $bind[$this->cleanBind($key)] = $value;

                else :

                    // add for replacement
                    $placeholderReplacement['{column}'][] = $value;

                endif;

            endif;

        endforeach; 

        // update statement and separator
        $separator = strrpos($statement, $separator);
        $statement = substr($statement, 0, $separator);

        // check replacement
        if (count($placeholderReplacement) > 0) :

            // merge replacement.
            $this->placeholderReplacement = array_merge($this->placeholderReplacement, $placeholderReplacement);

        endif;

        // return array
        return ['set' => $statement, 'bind' => $bind];
    }
}