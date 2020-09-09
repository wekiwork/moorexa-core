<?php
namespace Lightroom\Test;

use Closure, PDOStatement;
use Lightroom\Database\DatabaseHandler as Handler;
/**
 * @package Test Case Assertions
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait Assertions
{
    /**
     * @method TestCase assert_return
     * @param mixed $passed
     * @param mixed $expected
     * @return bool
     */
    private function assert_return($passed, $expected) : bool
    {
        // updated expected
        $expected = $expected == '0xx0xx' ? true : $expected;

        // check assertion
        return ($passed === $expected) ? true : ($this->flag == 1 ? false : null);
    }

    /**
     * @method TestCase assert_return_string
     * @param mixed $passed
     * @param mixed $expected
     * @return bool
     */
    private function assert_return_string($passed, $expected) : bool
    {
        // updated expected
        $expected = $expected == '0xx0xx' ? true : $expected;

        // check assertion
        return is_string($passed) ? $expected : ($this->flag == 1 ? false : null);
    }

    /**
     * @method TestCase assert_return_a_directory
     * @param mixed $passed
     * @param mixed $expected
     * @return bool
     */
    private function assert_return_a_directory($passed, $expected) : bool
    {
        // updated expected
        $expected = $expected == '0xx0xx' ? true : $expected;

        // check assertion
        return is_dir($passed) ? $expected : ($this->flag == 1 ? false : null);
    }

    /**
     * @method TestCase assert_return_status_code
     * @param mixed $expected
     * @param int $passed
     * @return bool
     */
    private function assert_return_status_code(&$expected, int $passed) : bool
    {
        // get response code
        $responseCode = http_response_code();

        // switch expected
        $expected = $responseCode;

        // check 
        return ($passed === $responseCode) ? true : ($this->flag == 1 ? false : null);
    }

    /**
     * @method TestCase assert_return_class
     * @param mixed $expected
     * @param int $passed
     * @return bool
     */
    private function assert_return_class($expected, string $passed) : bool
    {
        // get class name
        $getClassName = \is_object($expected) ? get_class($expected) : null;

        // check 
        return ($passed === $getClassName) ? true : ($this->flag == 1 ? false : null);
    }

    /**
     * @method TestCase assert_exception
     * @param Closure $closure
     * @param array $exeptions
     * @return bool
     */
    private function assert_exception(Closure $closure, ...$exeptions)
    {
       // @var bool $passed
       $passed = true;

       try
       {
           // load closure function
           call_user_func($closure);
       }
       catch(\Throwable $exception)
       {
            // get exception throwned
            $exceptionClass = get_class($exception);

            // @var int $exceptionSeen
            $exceptionSeen = 0;

            // check exceptions
            foreach ($exeptions as $className) :

                // compate class name
                if ($className == $exceptionSeen) $exceptionSeen++;

            endforeach;

            // update $passed
            if ($this->flag == 1) :
                // should
                $passed = $exceptionSeen == count($exeptions) ? true : false;
            else:
                // one or more
                $passed = $exceptionSeen == 0 ? null : true;
            endif;

            // make room
            if ($passed) :

                self::$assertionsFailed[] = [
                    'data' => '(' . $exception->getMessage() . ')',
                    'condition' => $exceptionClass . ' Exception Throwned.'
                ];

            endif;
       }

       // return boolean
       return $passed;
    }

    /**
     * @method TestCase assert_contain
     * @param mixed $value
     * @param array $arguments
     * @return bool
     */
    private function assert_contain($value, ...$arguments)
    {
        // continue with string 
        if (is_string($value)) :

            // find needle in string
            $findIndex = 0;

            // check now
            foreach ($arguments as $search) :
                // check for $search in string 
                if (strpos($value, $search) !== false) :
                    // update index
                    $findIndex++;
                    // remove string from $value
                    $value = str_replace($search, '', $value);
                endif;
            endforeach;

            // return bool
            return ($findIndex == \count($arguments)) ? true : ($this->flag == 1 ? false : null);
            
        elseif (is_array($value)) :

            // find index
            $findIndex = 0;

            // check now
            foreach ($value as $key => $value) :

                // run a check on argument
                foreach ($arguments as $search) :

                    // check value
                    if ($search === $value) $findIndex++;

                    // check key
                    if (!is_int($key) && $key === $value) $findIndex++;

                endforeach;

            endforeach;

            // return bool
            return ($findIndex == \count($arguments)) ? true : ($this->flag == 1 ? false : null);

        endif;  
    }

    /**
     * @method TestCase assert_query_insert
     * @param mixed $value
     * @param bool $expected
     * @return bool
     */
    private function assert_query_insert(&$value, $expected)
    {
        // @var bool $passed 
        $passed = false;

        // run helper
        $this->database_queries_helper($value, 'insert', $passed);

        // update value with expected
        $value = $passed;

        // updated expected
        $expected = $expected == '0xx0xx' ? true : $expected;

        // return bool
        return $passed == $expected ? true : ($this->flag == 1 ? false : null);
    }

    /**
     * @method TestCase assert_query_select
     * @param mixed $value
     * @param bool $expected
     * @return bool
     */
    private function assert_query_select(&$value, $expected)
    {
        // @var bool $passed 
        $passed = false;

        // run helper
        $this->database_queries_helper($value, 'select', $passed);

        // update value with expected
        $value = $passed;

        // updated expected
        $expected = $expected == '0xx0xx' ? true : $expected;

        // return bool
        return $passed == $expected ? true : ($this->flag == 1 ? false : null);
    }

    /**
     * @method TestCase assert_query_update
     * @param mixed $value
     * @param bool $expected
     * @return bool
     */
    private function assert_query_update(&$value, $expected)
    {
        // @var bool $passed 
        $passed = false;

        // run helper
        $this->database_queries_helper($value, 'update', $passed);

        // update value with expected
        $value = $passed;

        // updated expected
        $expected = $expected == '0xx0xx' ? true : $expected;

        // return bool
        return $passed == $expected ? true : ($this->flag == 1 ? false : null);
    }

    /**
     * @method TestCase assert_query_delete
     * @param mixed $value
     * @param bool $expected
     * @return bool
     */
    private function assert_query_delete(&$value, $expected)
    {
        // @var bool $passed 
        $passed = false;

        // run helper
        $this->database_queries_helper($value, 'delete', $passed);

        // update value with expected
        $value = $passed;

        // updated expected
        $expected = $expected == '0xx0xx' ? true : $expected;

        // return bool
        return $passed == $expected ? true : ($this->flag == 1 ? false : null);
    }

    /**
     * @method TestCase assert_query_sql
     * @param mixed $value
     * @param bool $expected
     * @return bool
     */
    private function assert_query_sql(&$value, $expected)
    {
        // @var bool $passed 
        $passed = false;

        // run helper
        $this->database_queries_helper($value, 'sql', $passed);

        // update value with expected
        $value = $passed;

        // updated expected
        $expected = $expected == '0xx0xx' ? true : $expected;

        // return bool
        return $passed == $expected ? true : ($this->flag == 1 ? false : null);
    }

    /**
     * @method Assertions database_queries_helper
     * @param mixed $value
     * @param string $method
     * @param bool $passed
     * @return void
     */
    private function database_queries_helper($value, string $method, bool &$passed) : void 
    {
        // $value is an object ?
        if (is_object($value) && get_class($value) == PDOStatement::class) :

            // check query in Handler
            $queriesExecuted = Handler::$queriesExecuted;

            if (isset($queriesExecuted[$method])) :

                // create dump
                $dump = '';

                // we would need to make use of ob_start, ob_get_contents, ob_clean
                ob_start();

                    // dump query
                    $value->debugDumpParams();

                    // get content
                    $dump = ob_get_contents();
                    
                ob_clean();

                // hash dump
                $dump = md5($dump);

                // find insertion
                foreach ($queriesExecuted[$method] as $query) :

                    if ($query['dump'] == $dump) :

                        // did query run
                        $passed = $query['statement']->rowCount() > 0 ? true : false;

                        break; // break out

                    endif;

                endforeach;

            endif;

        else:

            if (property_exists($value, 'uniqueid')) :

                // check query in Handler
                $queriesExecuted = Handler::$queriesExecuted;

                if (isset($queriesExecuted[$value->uniqueid])) :

                    if (isset($queriesExecuted[$value->uniqueid][$method])) $passed = true;

                endif;

            endif;

        endif;
    }

    /**
     * @method Assertions redirect_user
     * @param mixed $route_object
     * @return bool
     */
    private function assert_redirect_user(&$route_object) 
    {
        // @var bool $redirected
        $redirected = false;

        // check if route_object is an object
        if (is_object($route_object)) :

            // check redirect_count
            if (property_exists($route_object, 'info') && count($route_object->info) > 0) :

                // has user redirected
                $redirected = $route_object->info['redirect_count'] > 0 ? true : false;
                
            endif;

        endif;

        // check status
        $route_object = $redirected === false ? false : $route_object;
        
        // return boolean
        return $redirected;
    }
}