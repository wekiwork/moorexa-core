<?php
namespace Lightroom\Requests;

use Lightroom\Requests\Interfaces\CookieInterface;
use function Lightroom\Security\Functions\{encrypt, decrypt};
/**
 * @package Cookies
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
trait Cookies
{
    // This trait has some methods we need, so we don't have to repeat ourselves.
    use Session;

    /**
     * @method CookieInterface get
     * @param string $identifier
     * @return mixed
     * 
     * Gets a value from $_COOKIE with $identifier
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

                // decrypt value
                $value = decrypt($records[$identifierKey], $identifierKey);
            
            endif;
        
        endif;

        // return value
        return $value;
    }

    /**
     * @method CookieInterface set
     * @param string $identifier
     * @param string $value
     * @param array $options
     * @return CookieInterface
     * 
     * Sets a value in $_COOKIE with a key
     */
    public function set(string $identifier, string $value, array $options = []) : CookieInterface
    {
        // get driver
        $driver = $this->getDriverInstance();

        // update identifier
        $identifier = $this->getKey($identifier);

        // cookieOptions
        $cookieOptions = [];

        // load options
        $cookieOptions['cookie_expire']    = isset($options['expire']) ? (new \DateTime($options['expire']))->getTimestamp() : strtotime('30 days', time());
        $cookieOptions['cookie_path']      = isset($options['path']) ? $options['path'] : '/';
        $cookieOptions['cookie_domain']    = isset($options['domain']) ? $options['domain'] : '';
        $cookieOptions['cookie_secure']    = isset($options['secure']) ? $options['secure'] : 0;
        $cookieOptions['cookie_httponly']  = isset($options['httponly']) ? $options['httponly'] : true;

        if (is_array($driver)) :

            // extract variables
            extract($cookieOptions);

            // set options
            setcookie($identifier . '_options', encrypt(serialize($cookieOptions), $identifier));

            // set cookie
            setcookie($identifier, encrypt($value, $identifier), $cookie_expire, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);

        else:

            // run method from driver
            $driver->createRecord($identifier, $value, $cookieOptions);

        endif;


        // return instance
        return $this;
    }

    /**
     * @method CookieInterface setMultiple
     * @param array $multiple
     * @param array $options
     * @return CookieInterface
     * 
     * Sets multiple value in $_COOKIE with a key => value
     */
    public function setMultiple(array $multiple, array $options = []) : CookieInterface
    {
        foreach ($multiple as $identifier => $value) $this->set($identifier, $value, $options);

        // return instance
        return $this;
    }

    /**
     * @method CookieInterface drop
     * @param string $identifier
     * @return bool
     * 
     * Removes a key from $_COOKIE
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

            // has identifier
            if (isset($_COOKIE[$identifier])) :
                
                // drop cookie
                $dropped = true;

                // remove cookie
                $this->removeCookieAndOption($identifier);

            endif;

        else:

            // run method from driver
            $dropped = $driver->dropRecord($identifier);

        endif;

        // return bool
        return $dropped;
    }

    /**
     * @method Cookies except
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
     * @method Cookies __get
     * @param string $key 
     * @return string
     */
    public function __get(string $key) : string 
    {
        return $this->get($key);
    }

    /**
     * @method Cookies __set
     * @param string $key 
     * @return string
     */
    public function __set(string $key, $value) 
    {
        $this->set($key, $value);
    }

    /**
     * @method Cookies pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_POST with respective keys
     */
    public function pick(...$keys) : array
    {
        // @var array $picked
        $picked = [];

        // using foreach loop
        foreach ($keys as $key) :

            // add to picked
            $picked[$key] = $this->get($key);

        endforeach;

        // return array
        return $picked;
    }

    /**
     * @method CookieInterface all
     * @return array
     * 
     * Returns all $_COOKIE array
     */
    public function all() : array
    {
        // get instance or record
        $driver = $this->getDriverInstance();

        // get all records
        $records = [];

        // is array 
        if (is_array($driver)) :

            // update records
            $records = $_COOKIE;
        
        endif;

        // is object
        if (is_object($driver)) $records = $driver->getAll();

        // return records
        return $records;
    }

    /**
     * @method CookieInterface empty
     * @return bool
     * 
     * Returns true if $_COOKIE is empty
     */
    public function empty() : bool
    {
        return (count($this->all()) > 0) ? false : true;
    }

    /**
     * @method CookieInterface clear
     * @return bool
     * 
     * Clears the $_COOKIE array
     */
    public function clear() : bool
    {
        // get driver
        $driver = $this->getDriverInstance();

        // @var bool $emptied
        $emptied = false;

        if (is_array($driver)) :

            // get all cookies
            foreach ($_COOKIE as $key => $value) :

                // check for _ in key
                if (strpos($key, '_') !== false) :

                    // get identifier
                    $identifier = substr($key, strrpos($key, '_') + 1);

                    // update identifier
                    $identifier = $this->getKey($identifier);

                    // remove cookie
                    $this->removeCookieAndOption($identifier);

                endif;

            endforeach;
            
            // emptied
            $emptied = true;

        else:
            // run method from driver
            $emptied = $driver->emptyRecords();
        endif;

        // return bool
        return $emptied;
    }

    /**
     * @method Cookie getDriverAndDriverClass
     * @return array
     */
    private function getDriverAndDriverClass() : array 
    {
        return [env('cookie', 'driver'), env('cookie', 'class')];
    }

    /**
     * @method Cookie removeCookieAndOption
     * @param string $identifier
     * @return void
     */
    private function removeCookieAndOption(string $identifier) : void
    {
        if (isset($_COOKIE[$identifier . '_options'])) :

            // get options
            $options = unserialize(decrypt($_COOKIE[$identifier . '_options'], $identifier));

            // check if isset then remove
            if (is_array($options) && isset($_COOKIE[$identifier])) :

                // extract options
                extract($options);

                // remove cookie
                setcookie($identifier, '', time() - 3600, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);

                // remove options
                setcookie($identifier.'_options', '', time()-3600);

            endif;

        endif;
    }
}