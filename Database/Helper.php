<?php
namespace Lightroom\Database;

use PDO;
use Lightroom\Exceptions\{
    ClassNotFound, InterfaceNotFound
};
use Lightroom\Database\Interfaces\{
    ConfigurationInterface, DriverChannelInterface
};
use Lightroom\Adapter\ClassManager;
use Lightroom\Adapter\Configuration\Environment;
/**
 * @package Database helper trait
 * @author Amadi Ifeanyi
 */
trait Helper
{
    /**
     * @var array $configuration
     * 
     * Configuration data used for database connection.
     */
    private static $configuration = [];

    /**
     * @var string $injectAs
     */
    private $injectAs = '';

    /**
     * @var array $channelsRegistered
     */
    private static $channelsRegistered = [];

    /**
     * @var array $activeConnection
     */
    private static $activeConnection = [];

    /**
     * @method Helper loadQueryAllowed
     * @param array $arguments
     * @param string $sql
     * @return array
     * 
     * This returns an array of allowed methods for the query builder. This method was added here for
     * drivers who may want to utilize the default query builder.
     */
    public function loadQueryAllowed(array $arguments = [null], string &$sql = "") : array 
    {
        // create copy
        $original = $arguments;

        // return array
        return [
            'bind'      => "",
            'min'       => function() use (&$sql, $arguments){ $sql = str_replace('SELECT', 'SELECT MIN('.implode(',', $arguments).')', $sql); },
            'max'       => function() use (&$sql, $arguments){ $sql = str_replace('SELECT', 'SELECT MAX('.implode(',', $arguments).')', $sql); },
            'count'     => function() use (&$sql, $arguments){ $sql = str_replace('SELECT', 'SELECT COUNT('.implode(',', $arguments).')', $sql); },
            'avg'       => function() use (&$sql, $arguments){ $sql = str_replace('SELECT', 'SELECT AVG('.implode(',', $arguments).')', $sql); },
            'sum'       => function() use (&$sql, $arguments){ $sql = str_replace('SELECT', 'SELECT SUM('.implode(',', $arguments).')', $sql); },
            'distinct'  => function() use (&$sql, $arguments)
            {
                $sql = str_replace('SELECT', 'SELECT DISTINCT ' . implode(',', $arguments), $sql);
                $sql = str_replace('*', '', $sql);
            },
            'rand'      => ' ORDER BY RAND() ',
            'where'     => "",
            'whereString'=> function($arguments) use (&$sql)
            {
                if (is_array($arguments)) :

                    $newArgument = [];

                    foreach ($arguments as $key => $val) :

                        $newArgument[] = $key . ' = ' . $val;

                    endforeach;

                    $arguments = implode(' AND ', $newArgument);

                endif;

                $sql = str_replace('{where}', 'WHERE ' . $arguments, $sql);

            },
            'or'        => function() use ($arguments){ return ' OR '.implode(' OR ', $arguments).' '; },
            'as'        => function() use ($arguments){ return ' AS '.$arguments[0].' '; },
            'on'        => function() use ($arguments){ return ' ON '.$arguments[0].' '; },
            'join'      => function() use ($arguments, &$sql)
                        { 
                            $sql = str_replace('{where}', '{-where-}', $sql);
                            return ' JOIN '.$arguments[0].' '; 
                        },
            'innerJoin' => function() use ($arguments, &$sql)
                        { 
                            $sql = str_replace('{where}', '{-where-}', $sql);
                            return ' INNER JOIN '.$arguments[0].' '; 
                        },
            'outerJoin' => function() use ($arguments, &$sql)
                        { 
                            $sql = str_replace('{where}', '{-where-}', $sql);
                            return ' FULL OUTER JOIN '.$arguments[0].' ';
                        },
            'leftJoin'  => function() use ($arguments, &$sql)
                        { 
                            $sql = str_replace('{where}', '{-where-}', $sql);
                            return ' LEFT JOIN '.$arguments[0].' '; 
                        },
            'leftOuterJoin'  => function() use ($arguments, &$sql)
                        { 
                            $sql = str_replace('{where}', '{-where-}', $sql);
                            return ' LEFT OUTER JOIN '.$arguments[0].' '; 
                        },
            'rightJoin' => function() use ($arguments, &$sql)
                        { 
                            $sql = str_replace('{where}', '{-where-}', $sql);
                            return ' RIGHT JOIN '.$arguments[0].' '; 
                        },
            'rightOuterJoin'  => function() use ($arguments, &$sql)
                        { 
                            $sql = str_replace('{where}', '{-where-}', $sql);
                            return ' RIGHT OUTER JOIN '.$arguments[0].' '; 
                        },
            'from'      => function(array $tables) use (&$sql)
                        {

                            // add to placeholder replacement
                            if ($sql == '') :

                                foreach ($tables as $table => $closure) :

                                    if ($closure !== null && is_callable($closure)) :

                                        $this->placeholderReplacement['{table}'][] = trim($this->inject($closure, '')) . ' ' . $table;

                                    endif;

                                endforeach;

                            else:

                                // @var array $queries 
                                $queries = [];

                                foreach ($tables as $table => $closure) :

                                    if ($closure !== null && is_callable($closure)) :

                                       $queries[] = trim($this->inject($closure, '')) . ' ' . $table;

                                    endif;

                                endforeach;

                                // return string
                                return ' FROM ' . implode(',', $queries); 

                            endif;
                        },
            'fromWhere' => function() use ($arguments) 
                        { 
                            return ' FROM '.$this->from($arguments[0]).' WHERE ' . (isset($arguments[1]) ? $arguments[1] : null); 
                        },
            'in'        => function(\Closure $callback) use (&$sql) : string
                        { 
                            $sql = str_replace('{where}', '{-where-}', $sql);
                            
                            // return string
                            return $this->inject($callback, 'IN');
                        },
            'union'     => function(\Closure $callback) use (&$sql) : string
                        { 
                            $sql = str_replace('{where}', '{-where-}', $sql);
                            
                            // @var string $statement
                            $statement = $this->inject($callback, 'UNION');

                            // remove braces
                            $statement = rtrim($statement, ')');

                            // from begining
                            $statement = preg_replace('/(UNION)\s+([\(])/', 'UNION ', $statement);

                            // return string
                            return $statement;
                        },
            'unionAll'  => function(\Closure $callback) use (&$sql) : string
                        { 
                            $sql = str_replace('{where}', '{-where-}', $sql);
                            
                            // @var string $statement
                            $statement = $this->inject($callback, 'UNION ALL');

                            // remove braces
                            $statement = rtrim($statement, ')');

                            // from begining
                            $statement = preg_replace('/(UNION ALL)\s+([\(])/', 'UNION ALL ', $statement);

                            // return string
                            return $statement;
                        },
            'into'      => function() use (&$sql, $arguments){ $sql = str_replace('FROM', 'INTO '.$arguments[0].' FROM', $sql); },
            'and'       => function() use ($arguments) { return ' AND '.implode(' AND ', $arguments).' '; },
            'groupBy'   => function() use ($arguments) { return ' GROUP BY '.implode(',', $arguments).' '; },
            'except'    => ' EXCEPT ',
            'having'    => function() use ($arguments) { return ' HAVING '.$arguments[0].' '; },
            'any'       => function() use ($arguments) { return ' ANY ('.$arguments[0].') '; },
            'all'       => function() use ($arguments) { return ' ALL ('.$arguments[0].') '; },
            'not'       => function() use ($arguments) { return ' NOT '.implode(' NOT ', $arguments).' '; },
            'notin'     => function() use ($arguments) { return ' NOT IN ('.implode(',', $arguments).') '; },
            'between'   => function() use ($arguments) { return ' BETWEEN '.implode(' AND ', $arguments).' '; },
            'limit'     => function() use ($arguments) { return ' LIMIT '.implode(',', $arguments).' '; },
            'orderBy'   => function() use ($arguments) { return ' ORDER BY '.implode(' ', $arguments).' '; },
            'sql'       => function() use ($arguments) { return " ". (isset($arguments[0]) ? $arguments[0] : ''); },
            'get'       => '',
            'insert'    => '',
            'update'    => '',
            'delete'    => '',
            'interset'  => function(\Closure $callback) : string
            {
                // @var string $statement
                $statement = $this->inject($callback, 'INTERSECT');

                // remove braces
                $statement = rtrim($statement, ')');

                // from begining
                $statement = preg_replace('/(INTERSECT)\s+([\(])/', 'INTERSECT ', $statement);

                // return string
                return $statement;

            },
            'minus'  => function(\Closure $callback) : string
            {
                // @var string $statement
                $statement = $this->inject($callback, 'MINUS');

                // remove braces
                $statement = rtrim($statement, ')');

                // from begining
                $statement = preg_replace('/(MINUS)\s+([\(])/', 'MINUS ', $statement);

                // return string
                return $statement;

            },
            'if'        => function() use ($original)
            {
                if ($original[0] === true && is_callable($original[1])) :
                
                    // get callback
                    $callback = $original[1];
                    // copy instance
                    $current = &$this;
                    // call callback closure
                    call_user_func($callback, $current);

                endif;
            },
            'like'      => function($arguments) use ($sql){

                // create reference
                $argumentsCopy =& $arguments;
                // copy sql statement 
                $structure = $sql;
                $seperator = "and";
                $end = end($arguments);
                $seperator = $end == 'and' || $end == 'or' ? $end : $seperator;

                $line = $this->__stringBind($argumentsCopy[0], ' LIKE ', '');

                $where = $line['line'];
                $bind = $line['bind'];


                if (preg_match('/({where})/', $structure)) :
                
                    $structure = str_replace('{where}', 'WHERE '.$where.' ', $structure);
                    $this->query = $structure;
                    $this->lastWhere = 'WHERE '.$where.' ';
                
                else:
                
                    $this->query = trim($this->query) .' '.$seperator.' '. $where;
                    $lastWhere = substr($this->query, strpos($this->query, 'WHERE'));
                    $lastWhere = substr($lastWhere, 0, strrpos($lastWhere, $where)) . $where;
                    $this->lastWhere = $lastWhere;

                endif;

                array_shift($argumentsCopy);

                $this->__addBind($argumentsCopy, $bind, null);

                $newBind = [];

                // avoid clashes
                $this->__avoidClashes($bind, $newBind);

                // merge bind
                $this->bind = array_merge($this->bind, $newBind);
            },
            'exists'    => function(\Closure $callback) use (&$sql) : string
            {
                // @var string $statement
                $statement = $this->inject($callback, 'EXISTS');

                // check for placeholder
                if (strpos($sql, '{where}') !== false) :

                    // remove where
                    $sql = str_replace('{where}', '', $sql);

                    // add WHERE to statement
                    $statement = ' WHERE ' . $statement;

                endif;

                // return string
                return $statement;
            }
            
        ];
    }

    /**
     * @method Helper inject
     * @param \Closure $callback
     * @param string $keyword
     * @return string
     */
    public function inject(\Closure $callback, string $keyword = '', array &$bind = []) : string
    {
        // @var strirng $oldtable
        $oldtable = $this->table;

        // @var string alaise
        $alaise = $this->tableAlaise;

        // get builder instance
        $builder = ClassManager::singleton(static::class)->resetBuilder();

        // call closure
        call_user_func($callback, $builder);

        // merge bind
        $this->bind = array_merge($this->bind, $builder->bind);

        // merge bind
        $bind = array_merge($bind, $builder->bind);

        // reset
        $this->tableAlaise = $alaise;
        $this->table = $oldtable;

        // return string
        return strlen($builder->query) > 1 ? ' '.$keyword.' ('.$builder->query.')' . $builder->injectAs : '';
    }

    /**
     * @method Helper setAs
     * @param string $name
     * @return void
     */
    public function setAs(string $name) : void 
    {
        $this->injectAs = ' ' . $name;
    }

    /**
     * @method DatabaseHandlerInterface registerChannel
     * @param string $driver
     * @param string $channelClass
     * @return void 
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     */
    public static function registerChannel(string $driver, string $channelClass) : void 
    {
        if (strlen($channelClass) > 2) :
            
            // check for channel class
            if (!class_exists($channelClass)) throw new ClassNotFound($channelClass);

            // check if it implements DriverChannelInterface
            $reflection = new \ReflectionClass($channelClass);

            // throw exception
            if (!$reflection->implementsInterface(DriverChannelInterface::class)) throw new InterfaceNotFound($channelClass, DriverChannelInterface::class);

            // register channel
            self::$channelsRegistered[$driver] = $channelClass;

        endif;
    }

    /**
     * @method DatabaseHandlerInterface getChannel
     * @param string $driver
     * @return string 
     */
    public static function getChannel(string $driver = '') : string
    {
        // @var string $channel
        $channel = '';

        // update channel
        if ($driver != '' && isset(self::$channelsRegistered[$driver])) $channel = self::$channelsRegistered[$driver];

        // return channel
        return $channel;
    }
    
    /**
     * @method Helper getDSN
     * @param array $config
     * @param string $driver
     * @return string
     * 
     * Extract data source configuration into a string
     */
    private static function getDSN(array $config, string $driver) : string
    {
        // extract data source name (dsn)
        $dsn = isset($config['dsn']) ? $config['dsn'] : '{driver}:host={host};dbname={dbname};charset={charset}';

        // replace {driver} with mysql
        $dsn = str_replace('{driver}', $driver, $dsn);

        // manage dsn
        preg_match_all('/[{]\s{0}(\w+\s{0})\s{0}[}]/', $dsn, $matches);

        // using foreach loop
        foreach ($matches[1] as $val) :

            if (isset($config[$val])) :
            
                $dsn = str_replace('{'.$val.'}', $config[$val], $dsn);

            endif;

        endforeach;

        // return string
        return $dsn;
    }

    /**
     * @method Helper addUnixSocket
     * @param array $config
     * @param string $dsn
     * @param string $driver
     * @return string
     * 
     * Add unix socket if using development server
     */
    private static function addUnixSocket(array $config, string $dsn, string $driver) : string
    {
        // check if unix_socket is added
        if (!isset($config['unix_socket'])) :

            if (Environment::getEnv('database', 'mode') == 'development') :

                if (is_callable('shell_exec') && func()->is_disabled('shell_exec') === false) :

                    // get unix socket
                    if (strtolower(PHP_SHLIB_SUFFIX) != 'dll') :
                        $socks = shell_exec('netstat -ln | grep '.$driver);
                    else:
                        $socks = shell_exec('netstat -ln | findstr /i "'.$driver.'"');
                    endif;

                    $socks = trim(substr($socks, strpos($socks, '/')));

                    if (strlen($socks) > 3) :
                    
                        $socksPos = strpos($socks, '.sock');

                        if ($socksPos !== false) :
                        
                            $socks = substr($socks, 0, $socksPos) . '.sock';

                        endif;

                    endif;

                    if (mb_strlen($socks) > 1) $dsn .= ';unix_socket='.$socks;

                endif;

            endif;

        endif;

        // return string
        return $dsn;
    }
    
    /**
     * @method Helper connectWithDefaultPDO
     * @param ConfigurationInterface $config
     * @param string $driver
     * @return PDO
     * 
     * This method establishes a pdo connection. It's the default connection method for all the drivers
     */
    private static function connectWithDefaultPDO(ConfigurationInterface $config, string $driver) : PDO
    {
        // get attribute
        $attribute = $config->getOther('attributes');

        // use attributes
        $useAttribute = $attribute != '' ? $attribute == true ? true : false : false;

        // get all configuration
        $settings = $config->getConfiguration();

        // save data configuration
        self::$configuration = $settings;

        // add unix socket
        $dsn = self::addUnixSocket($settings, self::getDSN($settings, $driver), $driver);

        // @var string $dsnHash
        $dsnHash = md5($dsn);

        // create connection
        if (!isset(self::$activeConnection[$dsnHash])) :

            // add options
            $options = [
                PDO::ATTR_CASE => PDO::CASE_NATURAL,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => true
            ];

            // flatten options to single array
            if (isset($settings['options'])) $options = array_merge($options, $settings['options']);

            // get dbname
            $dbname = $config->getName();

            // try to establish connection
            $obj = new PDO($dsn, $config->getUser(), $config->getPass());

            // register channels
            if (isset($settings['channel'])) :

                // register channel
                self::registerChannel($settings['driver'], $settings['channel']);
                
            endif;

            // set attributes
            if ($useAttribute) :
                
                // load options
                foreach ($options as $attr => $val) :

                    // set attribute
                    $obj->setAttribute($attr, $val);

                endforeach;

            endif;

            // cache connection
            self::$activeConnection[$dsnHash] = $obj;

        else:

            // load from cache
            $obj = self::$activeConnection[$dsnHash];

        endif;

        return $obj;
    }
}