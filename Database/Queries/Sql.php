<?php
namespace Lightroom\Database\Queries;

use Lightroom\Database\Cache\QueryCache;

/**
 * @package QueryBuilder Sql Statement
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is the default sql statement method. Can be replaced whenever
 */
trait Sql
{
    /**
     * @method Sql sqlStatement
     * @param array $arguments
     * @return Sql|object
     */
    public function sqlStatement(...$arguments)
    {
        // set mixedData
        $mixedData = null;
        
        // check if not hashed
        if (QueryCache::isCached('sql', func_get_args(), $mixedData) === false) :

            // get sql statement
            $sqlStatement = $arguments[0];

            // remove statement
            array_shift($arguments);

            // continue with string
            if (is_string($sqlStatement) && strlen($sqlStatement) > 3) :
            
                // @var array bind
                $bind = [];
                // @var array newBind
                $newBind = [];
                // @var bool $getAssignment
                $getAssignment = true;

                // check for assignments
                if (isset($arguments[0]) && $arguments[0] === false) $getAssignment = false;

                // continue with assignment
                if ($getAssignment) :

                    // check for assignments
                    if (preg_match('/(=|!=|>|<|>=|<=)/', $sqlStatement)) :
                        
                        // get all assignments
                        preg_match_all('/\s{1,}([\S]+)\s{0,}(=|!=|>|<|>=|<=)\s{0,}[:|?|\'|"|0-9]/', $sqlStatement, $match);

                        foreach ($match[0] as $index => $assignment)
                        {
                            $assignment = trim($assignment);
                            // quote for regular expression
                            $quote = preg_quote($assignment);

                            // get the end of assignment
                            $end = substr($assignment,-1);

                            // end is ':' ?
                            if ($end == ':') :

                                // update assignment
                                preg_match("/($quote)([\S]+)/", $sqlStatement, $sql);
                                $assignment = trim($sql[0]);
                            
                            elseif (preg_match('/[0-9]/', $end)) :
                                
                                // update assignment
                                preg_match("/($quote)([\S]+|)/", $sqlStatement, $sql);
                                $assignment = trim($sql[0]);
                            
                            elseif (preg_match('/[\'|"]/', $end)) :
                                
                                // update assignment
                                preg_match("/($quote)([\s\S])['|\"|\S]+['|\"]/", $sqlStatement, $sql);
                                $assignment = isset($sql[0]) ? trim($sql[0]) : '';

                            endif;

                            if ($assignment != '') :

                                // run string bind
                                $stringBind = $this->__stringBind($assignment);

                                // update bind array
                                $bind[] = $stringBind['bind'];

                                // update statement
                                $sqlStatement = substr_replace($sqlStatement, $stringBind['line'], strpos($sqlStatement, $assignment), strlen($assignment));
                            
                            endif;

                            // remove index
                            unset($match[0][$index]);
                        }

                        // continue if $bind is greater than zero
                        if (count($bind) > 0) :
                        
                            // get bind value
                            foreach($bind as $bindValue) :
                                
                                // update new bind
                                if (is_array($bindValue)) foreach($bindValue as $key => $value) $newBind[$key] = $value;
    
                            endforeach;

                        endif;

                    endif;


                    // process sql binds

                    if (count($newBind) > 0) :
                    
                        // add bind
                        $this->__addBind($arguments, $newBind);

                        // @var array $newBind2
                        $newBind2 = [];

                        // avoid clashes
                        $this->__avoidClashes($newBind, $newBind2);

                        // update bind
                        $this->bind = array_merge($this->bind, $newBind2);

                    endif;

                endif;

                $this->query = $sqlStatement;

                $this->method = 'sql';

                return $this;

            endif;

            // save cache
            $mixedData !== null ? $mixedData($this->query, $this->bind) : null;

        else :

            // set the query method
            $this->method = 'sql';

            // get query and bind from hash
            $this->query = $mixedData['query'];
            $this->bind = $mixedData['bind'];

        endif;

        // clean up
        $hashName = null;

        // return object
        return (object) ['rows' => 0, 'row' => 0, 'error' => 'Invalid sql statement.'];
    }
}