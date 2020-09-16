<?php
namespace Lightroom\Database\Queries;

/**
 * @package Queries Helper
 * @author Amadi Ifeanyi
 */
trait QueriesHelper
{
      /**
     * @method QueriesHelper findCallbackFromArguments
     * @param array $arguments reference
     * @return mixed
     */
    private function findCallbackFromArguments(array &$arguments)
    {
        // run callback
        $runCallback = null;

        // check for any callback within arguments
        if (count($arguments) > 0) :
        
            // run through the list of arguments and seek for callback closure
            foreach ($arguments as $index => $argument) :
            
                if ($argument !== null && is_callable($argument)) :
                
                    // update $runCallback
                    $runCallback = $argument;

                    // remove argument.
                    unset($arguments[$index]);

                endif;

            endforeach;

        endif;

        // return mixed
        return $runCallback;
    }

    /**
     * @method QueriesHelper runStatementClosure
     * @param mixed $callback
     * @return void
     */
    private function runStatementClosure($callback) : void 
    {
        if (!is_null($callback) && get_class($callback) == \Closure::class) :

            // call closure and execute query
            call_user_func($callback, $this->go());

        endif;
    }

    /**
     * @method QueriesHelper decodeAddSlashesAndBind
     * @param string $key 
     * @param mixed $value
     * @param array $bind
     * @return void
     * 
     * This method decodes a value, add slashes to it if allowed, then add to the bind array
     */
    private function decodeAddSlashesAndBind(string $key, $value, array &$bind) : void
    {
        if (is_string($value)) :
              
            // decode value
            $value = html_entity_decode($value);

            // add slashes if allowed
            if (!$this->allowSlashes) $value = addslashes(stripslashes($value));

            // add to bind
            $bind[$key] = $value;

        else:
            // add to bind
            $bind[$key] = $value;
        endif;
    }

    /**
     * @method QueriesHelper buildQueryAndLastWhere
     * @param string $statement
     * @param string $where
     * @return void
     */
    private function buildQueryAndLastWhere(string $statement, string $where) : void 
    {
        // check if statement contains {where} placeholder
        if (preg_match('/({where})/', $statement)) :
                    
            // replace {where} in statement
            $statement = str_replace('{where}', 'WHERE '.$where.' ', $statement);

            // update query and last where property
            $this->query = $statement;
            $this->lastWhere = 'WHERE '.$where.' ';
        
        else:

            // keyword
            $keyword = $this->wherePrefix;

            // check for {-where-}
            if (strpos($this->query, '{-where-}') !== false) :

                // remove placeholder
                $this->query = str_replace('{-where-}', '', $this->query);

                // update keyword
                $keyword = ' WHERE ';

            endif;
            
            // update query
            $this->query = trim($this->query) . $keyword . $where;

            // get lastWhere
            $lastWhere = substr($this->query, strpos($this->query, 'WHERE'));
            $lastWhere = substr($lastWhere, 0, strrpos($lastWhere, $where)) . $where;

            // update last where
            $this->lastWhere = $lastWhere;

        endif;
    }

    /**
     * @method QueriesHelper reduceArray
     * @param array $array
     * @return array
     */
    private function reduceArray(array $array) : array 
    {
        return $this->__reduceArray($array, []);
    }

    /**
     * @method QueriesHelper __reduceArray
     * @param array $array
     * @param array $arrayNew
     * @return array
     */
    private function __reduceArray(array $array, array $arrayNew) : array
    {
        foreach ($array as $key => $value) :
        
            if (!is_array($value)) :
            
                $arrayNew[$key] = is_object($value) ? '' : $value;
            
            else:
            
                foreach($value as $childValue) :
                
                    if (!is_array($childValue)) :
                    
                        $arrayNew[$key] = is_object($childValue) ? '' : $childValue;
                    
                    else:
                    
                        $arrayNew = $this->__reduceArray($childValue, $arrayNew);
                    
                    endif;
                
                endforeach;

            endif;
        
        endforeach;

        return $arrayNew;
    }

    /**
     * @method QueriesHelper getArgumentsPassed
     * @return array
     */
    public function getArgumentsPassed() : array 
    {
        return $this->argumentPassed;
    }
}