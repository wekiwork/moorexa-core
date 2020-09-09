<?php
namespace Lightroom\Requests\Interfaces;

/**
 * @package Cookie Interface
 * @author Amadi Ifeanyi
 * 
 * This provides a quick abstraction for Cookie management
 */
interface CookieInterface
{
    /**
     * @method CookieInterface has
     * @param string $key
     * @return bool
     * 
     * Checks if $_COOKIE has a key
     */
    public function has(string $key) : bool;

    /**
     * @method CookieInterface hasMultiple
     * @param array $multiple
     * @return bool
     * 
     * Checks if $_COOKIE has multiple keys
     */
    public function hasMultiple(...$multiple) : bool;

    /**
     * @method CookieInterface get
     * @param string $key
     * @return mixed
     * 
     * Gets a value from $_COOKIE with $key
     */
    public function get(string $key);

    /**
     * @method CookieInterface set
     * @param string $key
     * @param string $value
     * @param array $options
     * @return CookieInterface
     * 
     * Sets a value in $_COOKIE with a key
     */
    public function set(string $key, string $value, array $options = []) : CookieInterface;

    /**
     * @method CookieInterface setMultiple
     * @param array $multiple
     * @param array $options
     * @return CookieInterface
     * 
     * Sets multiple value in $_COOKIE with a key => value
     */
    public function setMultiple(array $multiple, array $options = []) : CookieInterface;

    /**
     * @method CookieInterface drop
     * @param string $key
     * @return bool
     * 
     * Removes a key from $_COOKIE
     */
    public function drop(string $key) : bool;

    /**
     * @method CookieInterface dropMultiple
     * @param array $multiple
     * @return bool
     * 
     * Removes multiple keys from $_COOKIE
     */
    public function dropMultiple(...$multiple) : bool;

    /**
     * @method CookieInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_COOKIE with respective keys
     */
    public function pick(...$keys) : array;

    /**
     * @method CookieInterface except
     * @param array $keys 
     * @return array
     */
    public function except(...$keys) : array;

    /**
     * @method CookieInterface all
     * @return array
     * 
     * Returns all $_COOKIE array
     */
    public function all() : array;

    /**
     * @method CookieInterface empty
     * @return bool
     * 
     * Returns true if $_COOKIE is empty
     */
    public function empty() : bool;

    /**
     * @method CookieInterface clear
     * @return bool
     * 
     * Clears the $_COOKIE array
     */
    public function clear() : bool;
}