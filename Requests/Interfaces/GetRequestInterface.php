<?php
namespace Lightroom\Requests\Interfaces;

/**
 * @package Get Request Interface
 * @author Amadi Ifeanyi
 * 
 * This provides a quick abstraction for GET requests
 */
interface GetRequestInterface
{
    /**
     * @method GetRequestInterface has
     * @param string $key
     * @return bool
     * 
     * Checks if $_GET has a key
     */
    public function has(string $key) : bool;

    /**
     * @method GetRequestInterface hasMultiple
     * @param array $multiple
     * @return bool
     * 
     * Checks if $_GET has multiple keys
     */
    public function hasMultiple(...$multiple) : bool;

    /**
     * @method GetRequestInterface get
     * @param string $key
     * @return string
     * 
     * Gets a value from $_GET with a $key
     */
    public function get(string $key) : string;
    

    /**
     * @method GetRequestInterface set
     * @param string $key
     * @param mixed $value
     * @return GetRequestInterface
     * 
     * Sets a value in $_GET with a key
     */
    public function set(string $key, $value) : GetRequestInterface;

    /**
     * @method GetRequestInterface setMultiple
     * @param array $multiple
     * @return GetRequestInterface
     * 
     * Sets multiple value in $_GET with a key => value
     */
    public function setMultiple(array $multiple) : GetRequestInterface;

    /**
     * @method GetRequestInterface drop
     * @param string $key
     * @return bool
     * 
     * Removes a key from $_GET
     */
    public function drop(string $key) : bool;

    /**
     * @method GetRequestInterface pop
     * @param string $identifier
     * @return mixed
     * 
     * Returns the value of a get and removes it.
     */
    public function pop(string $identifier);

    /**
     * @method GetRequestInterface dropMultiple
     * @param array $multiple
     * @return bool
     * 
     * Removes multiple keys from $_GET
     */
    public function dropMultiple(...$multiple) : bool;

    /**
     * @method GetRequestInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_GET with respective keys
     */
    public function pick(...$keys) : array;

    /**
     * @method GetRequestInterface except
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_GET except keys
     */
    public function except(...$keys) : array;

    /**
     * @method GetRequestInterface all
     * @return array
     * 
     * Returns a filtered $_GET array
     */
    public function all() : array;

    /**
     * @method GetRequestInterface build
     * @param array $get
     * @return string
     * 
     * Returns a built query.
     */
    public function build(array $get) : string;

    /**
     * @method GetRequestInterface encode
     * @param string $get
     * @return string
     * 
     * Returns an encoded string
     */
    public function encode(string $get) : string;

    /**
     * @method GetRequestInterface decode
     * @param string $get
     * @return string
     * 
     * Returns a decoded string
     */
    public function decode(string $get) : string;

    /**
     * @method GetRequestInterface empty
     * @return bool
     * 
     * Returns true if $_GET is empty
     */
    public function empty() : bool;

    /**
     * @method GetRequestInterface clear
     * @return bool
     * 
     * Clears the $_GET array
     */
    public function clear() : bool;
}