<?php
namespace Lightroom\Database;

use database;
use Lightroom\Adapter\ClassManager;
use Lightroom\Database\ConnectionSettings;
use Lightroom\Requests\Drivers\Database\Session;
use Lightroom\Database\Drivers\Sqlite\{Schema, Driver};
use function Lightroom\Database\Functions\{db,map,db_with};
use function Lightroom\Requests\Functions\{session};
/**
 * @package DatabaseHandler StateManager
 * @author Amadi Ifeanyi <amadiify.com>
 */
class StateManagerClass
{
    /**
     * @var array $registered States
     */
    private static $registered = [];

    /**
     * @var string $caller
     */
    private $caller = '';

    /**
     * @var object $promise 
     */
    private $promise;

    /**
     * @var bool $driverRegistered
     */
    private static $driverRegistered = false;

    /**
     * @var array $configuration
     */
    private static $configuration = [];

    /**
     * @method StateManagerClass __construct
     * @param string $caller
     */
    public function __construct(string $stateName)
    {
        $this->caller = $stateName;
    }

    /**
     * @method StateManagerClass register
     * @param array $configuration
     * @return mixed
     */
    public function register(array $configuration) 
    {
        // use filter method
        $data = filter($configuration, [
            'callback' => 'required|string|a_class',
            'data' => 'required|an_array',
            'caller' => 'required|string',
            'table' => 'required|string'
        ]);

        // log error
        if (!$data->isOk()) :

            echo 'You have one or more errors in your state manager. Please check your error.log file';

            // log error
            return logger('monolog')->error('We couldn\'t register your state. See errors ', $data->getErrors());

        endif; 
        
        // configure session driver
        self::configureSessionDriver();

        // register state
        self::$registered[$data->caller] = $data->data();

        // save to session
        $states = session()->get('state-manager');

        // create array
        if (!is_array($states)) $states = [];

        // merge both
        $states = array_merge($states, self::$registered);

        // save now
        session()->set('state-manager', $states);

        // reset driver
        self::restoreSessionConfig();
    }

    /**
     * @method StateManagerClass __call
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        // @var array $manager
        $manager = $this->loadStateManager();

        // Can we continue
        if (count($manager) > 0) :

            // return from promise
            if ($this->promise !== null && method_exists($this->promise, $method)) return call_user_func_array([$this->promise, $method], $arguments);

            // @var string $callbackMethod
            $callbackMethod = 'query'.ucfirst($method);

            // check if static method exists
            $reflection = new \ReflectionClass($manager['callback']);

            // build query
            $query = db();

            // @var array $parameters
            $parameters = [
                'db' => &$query,
                'method' => &$method,
                'data' => $manager['data'],
                'arguments' => $arguments
            ];

            // check static method
            if ($reflection->hasMethod($callbackMethod)) :
                
                // mixed!
                $returnValue = call_user_func_array([$manager['callback'], $callbackMethod], [&$parameters]);

                // check if it's an object
                if (is_object($returnValue)) $query = $returnValue;

            endif;

            // manage query
            if (is_object($query) && !property_exists($query, 'method')) $query = db($method)->get($manager['data']);

            // can we run query
            if (method_exists($query, 'go')) return map($query);
        

        endif;

        // return instance
        return $this;
    }

    /**
     * @method StateManagerClass __get
     * @param string $column
     * @return mixed
     */
    public function __get(string $column)
    {
        // return from promise
        if ($this->promise !== null) return $this->promise->{$column};

        // @var array $manager
        $manager = $this->loadStateManager();

        if (count($manager) > 0) :

            // run query
            $this->promise = map(db($manager['table'])->get($manager['data']));

            // return data
            return $this->promise->{$column};

        endif;

        // return null
        return null;
    }

    /**
     * @method StateManagerClass remove
     * @return void
     */
    public function remove() : void
    {
        // @var array $manager
        $manager = $this->loadStateManager();

        // pull from state manager
        $states = session()->get('state-manager');
        
        // check if caller exists
        if (isset($states[$this->caller])) :

            // remove
            unset($states[$this->caller]);

            // configure session driver
            self::configureSessionDriver();

            // save now
            session()->set('state-manager', $states);

            // remove from $registered array
            unset(self::$registered[$this->caller]);

            // clean promise
            $this->promise = null;

            // restore session config
            self::restoreSessionConfig();

        endif;
    }

    /**
     * @method StateManagerClass refresh
     * @return void
     */
    public function refresh() : void 
    {
        // @var array $manager
        $manager = $this->loadStateManager();

        // run query
        if (count($manager) > 0) $this->promise = map(db($manager['table'])->get($manager['data']));
    }

    /**
     * @method StateManagerClass loadStateManager
     * @return array
     */
    private function loadStateManager() : array
    {
        // configure session driver
        self::configureSessionDriver();

        // @var array $returnValue
        $returnValue = [];

        // pull from state manager
        $states = session()->get('state-manager');

        // load states
        self::$registered = is_array($states) ? $states : self::$registered;

        if (count(self::$registered) > 0) :

            // load caller
            if ($this->caller != '') :

                // check for state
                if (!isset(self::$registered[$this->caller])) : throw new \Exception('Could not find state caller for "'.$this->caller.'"'); endif;

            else:

                // get the first state
                $this->caller = array_keys(self::$registered)[0];

            endif;

            // load state
            $returnValue = self::$registered[$this->caller];

        endif;

        // restore session config
        self::restoreSessionConfig();

        // return array
        return $returnValue;
    }

    /**
     * @method StatementManagerClass configureSessionDriver
     * @return void
     */
    private static function configureSessionDriver()
    {
        // load driver
        if (self::$driverRegistered === false) :

            // load database 
            ConnectionSettings::load([
                'state_manager_db' => [
                    'dsn' => 'sqlite:{database}',
                    'driver' => Driver::class,
                    'database' => __DIR__ . '/' . basename(env('database', 'state_manager_sqlite_db')),
                    'prefix' => ''
                ]
            ]);

            // save the configuration before we overwrite
            self::getSessionConfig();

            // get schema
            $schema = ClassManager::singleton(Schema::class);

            // create table
            $session = ClassManager::singleton(Session::class);

            // get schema
            $session->up($schema);

            // get connection
            $connection = db_with('state_manager_db');

            // set the table name
            $schema->tableName = $schema->table;

            // get query
            $getQuery = $connection->getQuery();

            // check table
            if ($getQuery->sql("SELECT * FROM pragma_table_info('{$schema->tableName}')")->rowCount() == 0) :

                // save schema
                $schema->saveSchema();

                // get jobs
                $jobs = $schema->sqljob;

                // run query
                if (count($jobs) > 0) $getQuery->sql($jobs[0]);

            endif;

            // register driver  
            self::$driverRegistered = true;

        endif;

        // overwrite the configuration
        env_set('session/driver', 'database');
        env_set('session/class', Session::class);

        // reset session
        session()->resetDriverInstance();

        // set the connection name
        Session::$connection_name = 'state_manager_db';
    }

    /**
     * @method StateManagerClass getSessionConfig
     * @return void
     */
    private static function getSessionConfig()
    {
        self::$configuration = [
            'driver' => env('session', 'driver'),
            'class' => env('session', 'class')
        ];
    }

    /**
     * @method StateManagerClass restoreSessionConfig
     * @return void
     */
    private static function restoreSessionConfig()
    {
        env_set('session/driver', self::$configuration['driver']);
        env_set('session/class', self::$configuration['class']);

        // set the connection name
        Session::$connection_name = '';
    }
}