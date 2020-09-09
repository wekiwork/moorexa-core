<?php
namespace Lightroom\Requests\Interfaces;

/**
 * @package Server Interface
 * @author Amadi Ifeanyi
 * 
 * This provides a quick abstraction for Server management
 */
interface ServerInterface
{
    /**
     * @method ServerInterface has
     * @param string $key
     * @return bool
     * 
     * Checks if $_SERVER has a key
     */
    public function has(string $key) : bool;

    /**
     * @method ServerInterface hasMultiple
     * @param array $multiple
     * @return bool
     * 
     * Checks if $_SERVER has multiple keys
     */
    public function hasMultiple(...$multiple) : bool;

    /**
     * @method ServerInterface get
     * @param string $key
     * @return string
     * 
     * Gets a value from $_SERVER with $key
     */
    public function get(string $key) : string;

    /**
     * @method ServerInterface set
     * @param string $key
     * @param mixed $value
     * @return ServerInterface
     * 
     * Sets a value in $_SERVER with a key
     */
    public function set(string $key, $value) : ServerInterface;

    /**
     * @method ServerInterface setMultiple
     * @param array $multiple
     * @return ServerInterface
     * 
     * Sets multiple value in $_SERVER with a key => value
     */
    public function setMultiple(array $multiple) : ServerInterface;

    /**
     * @method ServerInterface drop
     * @param string $key
     * @return bool
     * 
     * Removes a key from $_SERVER
     */
    public function drop(string $key) : bool;

    /**
     * @method ServerInterface dropMultiple
     * @param array $multiple
     * @return bool
     * 
     * Removes multiple keys from $_SERVER
     */
    public function dropMultiple(...$multiple) : bool;

    /**
     * @method ServerInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_SERVER with respective keys
     */
    public function pick(...$keys) : array;

    /**
     * @method ServerInterface except
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_SERVER except keys
     */
    public function except(...$keys) : array;

    /**
     * @method ServerInterface all
     * @return array
     * 
     * Returns all $_SERVER array
     */
    public function all() : array;

    /**
     * @method ServerInterface empty
     * @return bool
     * 
     * Returns true if $_SERVER is empty
     */
    public function empty() : bool;

    /**
     * @method ServerInterface clear
     * @return bool
     * 
     * Clears the $_SERVER array
     */
    public function clear() : bool;
}