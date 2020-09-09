<?php
namespace Lightroom\Requests\Interfaces;

/**
 * @package Post Request Interface
 * @author Amadi Ifeanyi
 * 
 * This provides a quick abstraction for POST requests
 */
interface PostRequestInterface
{
    /**
     * @method PostRequestInterface has
     * @param string $key
     * @return bool
     * 
     * Checks if $_POST has a key
     */
    public function has(string $key) : bool;

    /**
     * @method PostRequestInterface hasMultiple
     * @param array $multiple
     * @return bool
     * 
     * Checks if $_POST has multiple keys
     */
    public function hasMultiple(...$multiple) : bool;

    /**
     * @method PostRequestInterface get
     * @param string $key
     * @return string
     * 
     * Gets a value from $_POST with a $key
     */
    public function get(string $key) : string;

    /**
     * @method PostRequestInterface set
     * @param string $key
     * @param mixed $value
     * @return PostRequestInterface
     * 
     * Sets a value in $_POST with a key
     */
    public function set(string $key, $value) : PostRequestInterface;

    /**
     * @method PostRequestInterface setMultiple
     * @param array $multiple
     * @return PostRequestInterface
     * 
     * Sets multiple value in $_POST with a key => value
     */
    public function setMultiple(array $multiple) : PostRequestInterface;

    /**
     * @method PostRequestInterface drop
     * @param string $key
     * @return bool
     * 
     * Removes a key from $_POST
     */
    public function drop(string $key) : bool;

    /**
     * @method PostRequestInterface pop
     * @param string $identifier
     * @return mixed
     * 
     * Returns the value of a post and removes it.
     */
    public function pop(string $identifier);

    /**
     * @method PostRequestInterface dropMultiple
     * @param array $multiple
     * @return bool
     * 
     * Removes multiple keys from $_POST
     */
    public function dropMultiple(...$multiple) : bool;

    /**
     * @method PostRequestInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_POST with respective keys
     */
    public function pick(...$keys) : array;

    /**
     * @method PostRequestInterface except
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_POST except keys
     */
    public function except(...$keys) : array;

    /**
     * @method PostRequestInterface all
     * @return array
     * 
     * Returns a filtered $_POST array
     */
    public function all() : array;

    /**
     * @method PostRequestInterface getToken
     * @return string
     * 
     * Returns a csrf token used in form.
     */
    public function getToken() : string;

    /**
     * @method PostRequestInterface empty
     * @return bool
     * 
     * Returns true if $_POST is empty
     */
    public function empty() : bool;

    /**
     * @method PostRequestInterface method
     * @return string
     * 
     * Returns the REQUEST_METHOD
     */
    public function method() : string;

    /**
     * @method PostRequestInterface clear
     * @return bool
     * 
     * Clears the $_POST array
     */
    public function clear() : bool;
}