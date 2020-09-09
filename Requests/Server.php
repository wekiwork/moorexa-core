<?php
namespace Lightroom\Requests;

use Lightroom\Requests\Interfaces\ServerInterface;
/**
 * @package Server
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
trait Server
{
    /**
     * @var array $servers
     */
    private static $servers = [];
    
    /**
     * @method ServerInterface has
     * @param string $key
     * @return bool
     * 
     * Checks if $_SERVER has a key
     */
    public function has(string $key) : bool
    {
        // has server bool
        $hasServer = false;

        // get all
        $servers = $this->all();

        // check if key exists
        if (isset($servers[strtolower($key)])) :
            // update has server bool
            $hasServer = true;
        endif;

        // return bool
        return $hasServer;
    }

    /**
     * @method ServerInterface hasMultiple
     * @param array $multiple
     * @return bool
     * 
     * Checks if $_SERVER has multiple keys
     */
    public function hasMultiple(...$multiple) : bool
    {
        // has server bool
        $hasServer = false;

        // get count
        $success = 0;

        // run loop
        foreach ($multiple as $server_key) :

            // using $this->has method
            if ($this->has($server_key)) :
                // update success
                $success++;
            endif;

        endforeach;

        // if success is equivalent to size of multiple server keys
        // then we report a successful check
        if ($success == count($multiple)) :
            // update has server bool
            $hasServer = true;
        endif;

        // return bool
        return $hasServer;
    }

    /**
     * @method ServerInterface get
     * @param string $key
     * @param string $default
     * @return string
     *
     * Gets a value from $_SERVER with $key
     */
    public function get(string $key, string $default = '') : string
    {
        // get server
        $server = $this->all();

        // return string
        $returnString = $default;

        // check if it exists
        // didn't use $this->has() here, memory conscious :)
        if (isset($server[strtolower($key)])) :
            // get value
            $returnString = $server[strtolower($key)];
        endif;

        // return string
        return $returnString;
    }

    /**
     * @method ServerInterface set
     * @param string $key
     * @param mixed $value
     * @return ServerInterface
     * 
     * Sets a value in $_SERVER with a key
     */
    public function set(string $key, $value) : ServerInterface
    {
        // get all
        $server = $this->all();

        // set key value
        $server[$key] = is_string($value) ? filter_var($value, FILTER_SANITIZE_STRING) : $value;

        // set global
        self::$servers = $server;

        //return instance
        return $this;
    }

    /**
     * @method Server except
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
     * @method Server __get
     * @param string $key 
     * @return string
     */
    public function __get(string $key) : string 
    {
        return $this->get($key);
    }

    /**
     * @method Server __set
     * @param string $key 
     * @param mixed $value
     * @return string
     */
    public function __set(string $key, $value) 
    {
        $this->set($key, $value);
    }

    /**
     * @method ServerInterface setMultiple
     * @param array $multiple
     * @return ServerInterface
     * 
     * Sets multiple value in $_SERVER with a key => value
     */
    public function setMultiple(array $multiple) : ServerInterface
    {
        // using $this->set
        foreach ($multiple as $key => $value) :
            // set to global
            $this->set($key, $value);
        endforeach;

        // return instance
        return $this;
    }

    /**
     * @method ServerInterface drop
     * @param string $key
     * @return bool
     * 
     * Removes a key from $_SERVER
     */
    public function drop(string $key) : bool
    {
        // get all
        $server = $this->all();

        // dropped
        $dropped = false;

        // key
        $key = strtolower($key);

        // not using $this->has
        if (isset($server[$key])) :

            // drop and update dropped bool
            unset($server[$key]);

            // update dropped bool
            $dropped = true;

            // set globals
            self::$servers = $server;

        endif;

        // return dropped
        return $dropped;
    }

    /**
     * @method ServerInterface dropMultiple
     * @param array $multiple
     * @return bool
     * 
     * Removes multiple keys from $_SERVER
     */
    public function dropMultiple(...$multiple) : bool
    {
        // dropped
        $dropped = false;

        // we wouldn't judge by $drop_count, there might be one or more keys that are valid or not
        $drop_count = 0;

        // get keys
        foreach ($multiple as $server_key) :

            // using $this->drop()
            if ($this->drop($server_key)) :
                // update count
                $drop_count++;
            endif;

        endforeach;

        // we would assume true if $drop_count is at least greater than or equal to one
        if ($drop_count >= 1) :
            // update dropped
            $dropped = true;
        endif;

        // return dropped
        return $dropped;
    }

    /**
     * @method ServerInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_SERVER with respective keys
     */
    public function pick(...$keys) : array
    {
        // multiple values
        $values = [];   

        // using foreach
        foreach ($keys as $key) :

            // push to value
            $values[$key] = $this->get($key);

        endforeach;


        // return values
        return $values;
    }

    /**
     * @method ServerInterface all
     * @return array
     * 
     * Returns all $_SERVER array
     */
    public function all() : array
    {
        // get servers
        $server = null;

        // return servers if not empty
        if (count(self::$servers) > 0) :
            // get server
            $server = self::$servers;
        endif;

        // convert keys to lowercase
        if ($server === null) :

            // get server from $_SERVER 
            $server = $_SERVER;

            // convert key to lowercase
            foreach ($server as $key => $value) :

                // make lower case
                $key = strtolower($key);

                // save to global variable
                self::$servers[$key] = is_string($value) ? filter_var($value, FILTER_SANITIZE_STRING) : $value;

            endforeach;

            // return server
            $server = self::$servers;

        endif;
        

        return $server;
    }

    /**
     * @method ServerInterface empty
     * @return bool
     * 
     * Returns true if $_SERVER is empty
     */
    public function empty() : bool
    {
        // get all
        $all = $this->all();

        // check data
        return count($all) > 0 ? false : true;
    }

    /**
     * @method ServerInterface clear
     * @return bool
     * 
     * Clears the $_SERVER array
     */
    public function clear() : bool
    {
        // get all
        $all = $this->all();

        // get keys
        $keys = array_keys($all);

        // drop multiple
        call_user_func_array([$this, 'dropMultiple'], $keys);

        // clean up
        $all = null;

        // return boolean
        return true;
    }
}