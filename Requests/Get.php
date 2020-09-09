<?php
namespace Lightroom\Requests;

use Lightroom\Requests\Interfaces\GetRequestInterface;
/**
 * @package Get
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
trait Get
{
    /**
     * @method GetRequestInterface has
     * @param string $key
     * @return bool
     * 
     * Checks if $_GET has a key
     */
    public function has(string $key) : bool
    {
        return isset($_GET[$key]) ? true : false;
    }

    /**
     * @method GetRequestInterface hasMultiple
     * @param array $multiple
     * @return bool
     * 
     * Checks if $_GET has multiple keys
     */
    public function hasMultiple(...$multiple) : bool
    {
        // @var int $count
        $count = 0;

        // using foreach
        foreach ($multiple as $key):

            // checking
            if ($this->has($key)) $count++;

        endforeach;

        // return bool
        return ($count == count($multiple)) ? true : false;
    }

    /**
     * @method GetRequestInterface get
     * @param string $key
     * @return string
     * 
     * Gets a value from $_GET with a $key
     */
    public function get(string $key, string $default = '') : string
    {
        // get all
        $all = $this->all();

        // return string
        return isset($all[$key]) ? $all[$key] : $default;
    }

    /**
     * @method Get __get
     * @param string $key 
     * @return string
     */
    public function __get(string $key) : string 
    {
        return $this->get($key);
    }

    /**
     * @method Get except
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
     * @method Get __set
     * @param string $key 
     * @param mixed $value
     * @return string
     */
    public function __set(string $key, $value) 
    {
        $this->set($key, $value);
    }

    /**
     * @method GetRequestInterface set
     * @param string $key
     * @param mixed $value
     * @return GetRequestInterface
     * 
     * Sets a value in $_GET with a key
     */
    public function set(string $key, $value) : GetRequestInterface
    {
        // set var
        $_GET[$key] = $value;

        // return instance
        return $this;
    }
    

    /**
     * @method GetRequestInterface setMultiple
     * @param array $multiple
     * @return GetRequestInterface
     * 
     * Sets multiple value in $_GET with a key => value
     */
    public function setMultiple(array $multiple) : GetRequestInterface
    {
        // using foreach loop
        foreach ($multiple as $key => $value):

            // set key, value
            $this->set($key, $value);

        endforeach;

        // return instance
        return $this;
    }

    /**
     * @method GetRequestInterface drop
     * @param string $key
     * @return bool
     * 
     * Removes a key from $_GET
     */
    public function drop(string $key) : bool
    {
        // dropped
        $dropped = false;

        // check if key exists
        if (isset($_GET[$key])) :

            // drop key
            unset($_GET[$key]);

            // update dropped
            $dropped = true;

        endif;


        // return bool
        return $dropped;
    }

    /**
     * @method GetRequestInterface pop
     * @param string $identifier
     * @return mixed
     * 
     * Returns the value of a get and removes it.
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
     * @method GetRequestInterface dropMultiple
     * @param array $multiple
     * @return bool
     * 
     * Removes multiple keys from $_GET
     */
    public function dropMultiple(...$multiple) : bool
    {
        // dropped
        $dropped = false;

        // drop count
        $count = 0;

        // using foreach loop
        foreach ($multiple as $key) :

            if ($this->drop($key)) $count++;

        endforeach;

        // update dropped
        if ($count >= 1) $dropped = true;

        // return bool
        return $dropped;
    }

    /**
     * @method GetRequestInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_GET with respective keys
     */
    public function pick(...$keys) : array
    {
        // get all 
        $get = $this->all();

        // @var array picked
        $picked = [];

        // using foreach loop
        foreach ($keys as $key) :

            // check if key exists
            $picked[$key] = isset($get[$key]) ? $get[$key] : false;

        endforeach;

        // return array
        return $picked;
    }

    /**
     * @method GetRequestInterface all
     * @return array
     * 
     * Returns a filtered $_GET array
     */
    public function all() : array
    {
        // @var array get
        $get = $_GET;

        // run loop and clean it
        foreach ($get as $key => $value) :

            // clean value
            $value = strip_tags($value);

            // decode value
            $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

            // set value
            $get[$key] = $value;

        endforeach;

        // return array
        return $get;
    }   

    /**
     * @method GetRequestInterface build
     * @param array $get
     * @return string
     * 
     * Returns a built query.
     */
    public function build(array $get) : string
    {
        return $this->encode(http_build_query($get));
    }

    /**
     * @method GetRequestInterface encode
     * @param string $value
     * @return string
     *
     * Returns an encoded string
     */
    public function encode(string $value) : string
    {
        // using raw url encode
        return rawurlencode($value);
    }

    /**
     * @method GetRequestInterface decode
     * @param string $value
     * @return string
     *
     * Returns a decoded string
     */
    public function decode(string $value) : string
    {
        // using raw url decode
        return rawurldecode($value);
    }

    /**
     * @method GetRequestInterface empty
     * @return bool
     * 
     * Returns true if $_GET is empty
     */
    public function empty() : bool
    {
        return count($_GET) == 0 ? true : false;
    }

    /**
     * @method GetRequestInterface clear
     * @return bool
     * 
     * Clears the $_GET array
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