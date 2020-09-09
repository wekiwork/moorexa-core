<?php
namespace Lightroom\Database\Drivers;

use PDO, PDOStatement, PDOException;
use Lightroom\Database\DatabaseChannel;
use Lightroom\Database\DatabaseHandler;
/**
 * @package Drivers Helper Trait
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 *
 * This Provides 2 helper methods for the query class in our drivers
 */
trait DriversHelper
{
    /**
     * @var array $prepareCache
     */
    private static $prepareCache = [];

    /**
     * @var string $queryMethod
     */
    private static $queryMethod = '';

    /**
     * @method DriversHelper queryListenerFor
     * @param string $driver
     * @param string $method
     * @param string $table
     * @param string &$query
     * @param array &$bind
     * @return void
     */
    public static function queryListenerFor(string $driver, string $method, string $table, string &$query, array &$bind) : void
    {
        // get channel
        $channel = call_user_func([$driver, 'getChannel'], $driver);

        // make method global
        self::$queryMethod = $method;

        // has channel
        if (strlen($channel) > 1) :

            // load database channel class
            $databaseChannel = call_user_func_array([
                DatabaseChannel::class, 'loadInstance'
            ], [$driver, [
                'query'     => $query,
                'bind'      => $bind,
                'table'     => $table,
                'method'    => $method,
                'origin'    => 'query'
            ]]);

            // call channel
            call_user_func_array([$channel, 'ready'], [$method, $databaseChannel]);

            // update query
            $query = $databaseChannel->getQuery();

            // update bind
            $bind = $databaseChannel->getBind();

        endif;
    }

    /**
     * @method DriversHelper prepare
     * @param string $query
     * @param PDO $pdoInstance
     * @param array $bind
     * 
     * Prepares PDO query
     */
    private function prepare(string $query, PDO $pdoInstance, array $bind = []) : PDOStatement
    {
        if (strlen($query) > 4) :

            if ($pdoInstance != null) :
            
                // use transactions.
                if (method_exists($pdoInstance, 'inTransaction') && $pdoInstance->inTransaction() === false) :
                    
                    // begin transaction
                    if (method_exists($pdoInstance, 'beginTransaction')) $pdoInstance->beginTransaction();

                endif;

                // check cache
                $cache = md5(implode('',$bind) . $query);

                if (!isset(self::$prepareCache[$cache])) :

                    // prepare query
                    $smt = $pdoInstance->prepare($query);

                    if (is_bool($smt)) :

                        if (env('bootstrap', 'debug_mode') === true) :
                            echo 'You have an error in your database query. Please check your error.log file' . "\n";
                        endif;

                        // get the info
                        $info = $pdoInstance->errorinfo();

                        // log error
                        logger('monolog')->error($info[2], ['statement' => $query]);

                    endif;

                    if (count($bind) > 0 && is_object($smt)) :
                    
                        // @var int $index
                        $index = 0;

                        // update bind
                        foreach ($bind as $key => $val) :
                        
                            if (is_array($val) && isset($val[$index]))  :
                            
                                $val = $val[$index];
                                $index++;

                            endif;

                            // get value from closure
                            if (!is_null($val) && is_callable($val))  $val = call_user_func($val);

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

                    // cache query
                    self::$prepareCache[$cache] = $smt;
                
                else:

                    // get cache
                    $smt = self::$prepareCache[$cache];
                
                endif;

                return is_object($smt) ? $smt : (new PDOStatement);

            endif;

        endif;

        return null;
    }

    /**
     * @method DriversHelper prepareBinds
     * @param array $bind
     * @param string $queryMethod
     * @param string $sqlQuery
     * @return array
     * 
     * Prepares Bind, fill the empty binds with values
     */
    private function prepareBinds(array $bind, string $queryMethod, string $sqlQuery) : array
    {
        // good
        if ($queryMethod == 'select') :
                
            // fill in the gap
            foreach ($bind as $key => $val) :
            
                if (is_null($val) || (is_string($val) && strlen($val) == 0)) :
                
                    foreach ($bind as $value) :
                    
                        if (!empty($value)) :
                        
                            $bind[$key] = $value;
                            break;

                        endif;

                    endforeach;

                endif;

            endforeach;

            // remove placeholder {where}
            $sqlQuery = str_replace('{where}', '', $sqlQuery);
            
        endif;

        // return array
        return [$bind, $sqlQuery];
    }

    /**
     * @method DriversHelper execute
     * @param PDOStatement $statement
     * @param PDO $pdoInstance
     * @return PDOStatement
     * 
     * Executes a pdo statement
     */
    private function execute(PDOStatement &$statement, PDO &$pdoInstance) : PDOStatement
    {
        try 
        {
            // execute query
            $statement->execute();
            
            if (method_exists($pdoInstance, 'commit') && self::$queryMethod == 'select') :

                // commit transaction
                if ($pdoInstance->inTransaction()) $pdoInstance->commit();

            endif;

            // query ran
            DatabaseHandler::queryRanSuccessfully($statement, self::$queryMethod);

            // load subscribers
            if (self::$queryMethod != 'select') DatabaseHandler::loadSubscribers($statement, $pdoInstance);

        }
        catch(\Throwable $exception)
        {
            if (method_exists($pdoInstance, 'inTransaction') && $pdoInstance->inTransaction() === true) :

                // rollback transaction
                $pdoInstance->rollBack();

            endif;

            // manage error
            DatabaseHandler::errorManager($exception);
        }

        // return statement
        return $statement;
    }
}