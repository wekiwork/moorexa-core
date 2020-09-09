<?php
namespace Lightroom\Requests;

use Exception;
use Lightroom\Exceptions\{
    ClassNotFound, InterfaceNotFound
};
use Lightroom\Requests\Interfaces\{
    SessionInterface, DatabaseDriverInterface
};
use Lightroom\Requests\Drivers\DriversHelper;
use function Lightroom\Security\Functions\{encrypt, decrypt};
/**
 * @package Session
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
trait Session
{
    use DriversHelper;

    /**
     * @var Session $driverInstance
     */
    private static $driverInstance;

    /**
     * @method SessionInterface has
     * @param string $identifier
     * @return bool
     * 
     * Checks if $_SESSION has an identifier
     */
    public function has(string $identifier) : bool
    {
        // @var bool $hasIdentifier
        $hasIdentifier = false;

        // update identifier
        $identifier = $this->getKey($identifier);

        // get all records
        $records = $this->all();

        // check if is set
        if (isset($records[$identifier])) $hasIdentifier = true;

        // return bool
        return $hasIdentifier;
    }

    /**
     * @method SessionInterface hasMultiple
     * @param array $multiple
     * @return bool
     * 
     * Checks if $_SESSION has multiple keys
     */
    public function hasMultiple(...$multiple) : bool
    {
        // @var bool $hasMultiple
        $hasMultiple = false;

        // @var int $found
        $found = 0;

        // get all records
        $records = $this->all();

        // run multiple check
        foreach ($multiple as $identifier) :

            // update identifier
            $identifier = $this->getKey($identifier);

            // check if identifier exists
            if (isset($records[$identifier])) $found++;

        endforeach;

        // return bool
        return ($found == count($multiple)) ? true : false;
    }

    /**
     * @method SessionInterface get
     * @param string $identifier
     * @return mixed
     * 
     * Gets a value from $_SESSION with $identifier
     */
    public function get(string $identifier)
    {
        // @var bool $value
        $value = false;
        
        // get all records
        $records = $this->all();

        // continue if array
        if (is_array($records)) :

            // @var string $identifierKey
            $identifierKey = $this->getKey($identifier);

            // check if identifier exists
            if (isset($records[$identifierKey])) :

                // unserialize value
                $decrypted = decrypt($records[$identifierKey], $identifierKey);

                if (strlen($decrypted) > 2) :

                    // unserialize
                    $decrypted = unserialize($decrypted);

                    // extract value
                    $value = $decrypted['session_value'];

                    // check revalidation
                    $checkRevalidation = true;

                    // session has expire to it ?
                    if (isset($decrypted['expire_in'])) :

                        // compare timestamp
                        if ($decrypted['expire_in'] < time()) :

                            // expired
                            $checkRevalidation = false;

                            // update value
                            $value = false;

                            // drop session
                            $this->drop($identifier);
                            
                        endif;
                         
                    endif;

                    // check if revalidate_before exists
                    if ($checkRevalidation === true && isset($decrypted['revalidate_before'])) :

                        // do we have time ?
                        if ($decrypted['revalidate_before'] >= time()) :

                            // remove value
                            unset($decrypted['session_value']);

                            // add more time
                            $this->set($identifier, $value, $decrypted);
                        else:

                            // update value
                            $value = false;

                            // drop identifier
                            $this->drop($identifier);
                        endif;

                    endif;

                endif;

            endif;

        endif;

        // return string
        return $value;
    }

    /**
     * @method SessionInterface set
     * @param string $identifier
     * @param mixed $value
     * @param array $options
     * @return SessionInterface
     * 
     * Sets a value in $_SESSION with an identifier
     */
    public function set(string $identifier, $value, array $options = []) : SessionInterface
    {
        // get driver
        $driver = $this->getDriverInstance();

        // using array
        $value = array_merge(['session_value' => $value], $options);

        // check for revalidation rule
        if (isset($value['revalidate'])) :

            // add time to revalidate
            $value['revalidate_before'] = strtotime($value['revalidate'], time());

        endif;

        // check for expire rule
        if (isset($value['expire'])) :

            // add expire time
            $value['expire_in'] = strtotime($value['expire'], time());

        endif;

        // serialize value
        $value = serialize($value);

        // update identifier
        $identifier = $this->getKey($identifier);

        // use default if array
        if (is_array($driver)) :

            // get all
            $records = $this->all();

            // add identifier
            $records[$identifier] = encrypt($value, $identifier);

            // set session
            $_SESSION[$this->getSessionKey()] = serialize($records);

        else:

            // run method from driver
            $driver->createRecord($identifier, $value);

        endif;

        // return instance
        return $this;
    }

    /**
     * @method SessionInterface setMultiple
     * @param array $multiple
     * @param array $options
     * @return SessionInterface
     * 
     * Sets multiple value in $_SESSION with a key => value
     */
    public function setMultiple(array $multiple, array $options = []) : SessionInterface
    {
        foreach ($multiple as $identifier => $value) $this->set($identifier, $value, $options);

        // return instance
        return $this;
    }

    /**
     * @method SessionInterface drop
     * @param string $identifier
     * @return bool
     * 
     * Removes a key from $_SESSION
     */
    public function drop(string $identifier) : bool
    {
        // get driver
        $driver = $this->getDriverInstance();

        // update identifier
        $identifier = $this->getKey($identifier);

        // @var bool $dropped
        $dropped = false;

        // use default if array
        if (is_array($driver)) :

            // @var string $userAgentHash
            $userAgentHash = $this->getSessionKey();

            // has user agent
            if (isset($_SESSION[$userAgentHash])) :
                
                // get all
                $records = $this->all();

                // check if identifier exists
                if (isset($records[$identifier])) :

                    // unset session
                    unset($records[$identifier]);

                    // update session
                    $_SESSION[$userAgentHash] = serialize($records);

                    // drop session
                    $dropped = true;

                endif;

            endif;

        else:

            // run method from driver
            $dropped = $driver->dropRecord($identifier);

        endif;

        // return bool
        return $dropped;
    }

    /**
     * @method SessionInterface pop
     * @param string $identifier
     * @return mixed
     * 
     * Returns the value of a session and removes it.
     */
    public function pop(string $identifier)
    {
        // get the value
        $value = $this->get($identifier);

        // remove it
        $this->drop($identifier);

        // return value
        return $value;
    }

    /**
     * @method SessionInterface dropMultiple
     * @param array $multiple
     * @return bool
     * 
     * Removes multiple keys from $_SESSION
     */
    public function dropMultiple(...$multiple) : bool
    {
        // @var int $dropCount
        $dropCount = 0;

        // drop
        foreach ($multiple as $identifier) if ($this->drop($identifier)) $dropCount++;

        // return drop
        return ($dropCount == count($multiple)) ? true : false;
    }

    /**
     * @method Session except
     * @param array $keys 
     * @return array
     */
    public function except(...$keys) : array 
    {
        // @var array $picked
        $picked = $this->all();

        // using foreach loop
        foreach ($keys as $key) :

            // add to picked
            if (isset($picked[$key])) unset($picked[$key]);

        endforeach;

        // return array
        return $picked;
    }

    /**
     * @method Session __get
     * @param string $key 
     * @return string
     */
    public function __get(string $key) : string 
    {
        return $this->get($key);
    }

    /**
     * @method Session __set
     * @param string $key 
     * @return string
     */
    public function __set(string $key, $value) 
    {
        $this->set($key, $value);
    }

    /**
     * @method SessionInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_SESSION with respective keys
     */
    public function pick(...$keys) : array
    {
        // @var array $newArray
        $newArray = [];

        // find keys
        foreach ($keys as $key) :

            // get value
            $value = $this->get($key);

            // push to new array if value was returned
            if ($value !== false) $newArray[$key] = $value;

        endforeach;

        // return new array
        return $newArray;
    }

    /**
     * @method SessionInterface all
     * @return array
     * 
     * Returns all $_SESSION array
     */
    public function all() : array
    {
        // get instance or record
        $driver = $this->getDriverInstance();

        // get all records
        $records = [];

        // is array 
        if (is_array($driver)) :

            // @var string $userAgentHash
            $userAgentHash = $this->getSessionKey();

            // get session
            $session = isset($_SESSION[$userAgentHash]) ? unserialize($_SESSION[$userAgentHash]) : [];

            // update records
            $records = $session;
        
        endif;

        // is object
        if (is_object($driver)) $records = $driver->getAll();

        // return records
        return $records;
    }

    /**
     * @method SessionInterface empty
     * @return bool
     * 
     * Returns true if $_SESSION is empty
     */
    public function empty() : bool
    {
        return (count($this->all()) > 0) ? false : true;
    }

    /**
     * @method SessionInterface clear
     * @return bool
     * 
     * Clears the $_SESSION array
     */
    public function clear() : bool
    {
        // get driver
        $driver = $this->getDriverInstance();

        // @var bool $emptied
        $emptied = false;

        if (is_array($driver)) :

            // @var string $userAgentHash
            $userAgentHash = $this->getSessionKey();

            // empty session
            if (isset($_SESSION[$userAgentHash])) unset($_SESSION[$userAgentHash]);
            
            // emptied
            $emptied = true;

        else:

            $emptied = $driver->emptyRecords();

        endif;

        // return bool
        return $emptied;
    }

    /**
     * @method Session getDriverAndDriverClass
     * @return array
     */
    private function getDriverAndDriverClass() : array 
    {
        return [env('session', 'driver'), env('session', 'class')];
    }

    /**
     * @method Session resetDriverInstance
     * @return void
     */
    public function resetDriverInstance() : void
    {
        self::$driverInstance = null;
    }

    /**
     * @method Session getDriverInstance
     * @return mixed
     * @throws ClassNotFound
     * @throws Exception
     * @throws InterfaceNotFound
     */
    private function getDriverInstance()
    {
        if (self::$driverInstance === null) :

            // try get driver and class
            list($driver, $driverClass) = $this->getDriverAndDriverClass();

            // interfaces for drivers
            $interfaces = [
                'database' => DatabaseDriverInterface::class
            ];

            // default to array
            self::$driverInstance = [];

            // driver class
            if ($driverClass !== null && is_string($driverClass) && is_string($driver)) :

                if (strtolower($driver) != 'default') :

                    // ensure class exits
                    if (!class_exists($driverClass)) throw new ClassNotFound($driverClass);

                    // create reflection class
                    $reflection = new \ReflectionClass($driverClass);

                    // check if support has been created
                    if (!isset($interfaces[strtolower($driver)])) throw new Exception('Driver "'.$driver.'" not currently in support.');

                    // get interface
                    $interface = $interfaces[strtolower($driver)];

                    // ensure class implements DatabaseDriverInterface
                    if (!$reflection->implementsInterface($interface)) throw new InterfaceNotFound($driverClass, $interface);

                    // load instance
                    self::$driverInstance = $reflection->newInstanceWithoutConstructor();

                endif;

            endif;

        endif;

        // return instance
        return self::$driverInstance;
    }
}