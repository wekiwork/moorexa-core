<?php
namespace Lightroom\Requests\Interfaces;

/**
 * @package Session Interface
 * @author Amadi Ifeanyi
 * 
 * This provides a quick abstraction for Session management
 */
interface SessionInterface
{
    /**
     * @method SessionInterface has
     * @param string $identifier
     * @return bool
     * 
     * Checks if $_SESSION has an identifier
     */
    public function has(string $identifier) : bool;

    /**
     * @method SessionInterface hasMultiple
     * @param array $multiple
     * @return bool
     * 
     * Checks if $_SESSION has multiple keys
     */
    public function hasMultiple(...$multiple) : bool;

    /**
     * @method SessionInterface get
     * @param string $identifier
     * @return mixed
     * 
     * Gets a value from $_SESSION with $identifier
     */
    public function get(string $identifier);

    /**
     * @method SessionInterface set
     * @param string $identifier
     * @param mixed $value
     * @param array $options
     * @return SessionInterface
     * 
     * Sets a value in $_SESSION with an identifier
     */
    public function set(string $identifier, $value, array $options = []) : SessionInterface;

    /**
     * @method SessionInterface setMultiple
     * @param array $multiple
     * @param array $options
     * @return SessionInterface
     * 
     * Sets multiple value in $_SESSION with a key => value
     */
    public function setMultiple(array $multiple, array $options = []) : SessionInterface;

    /**
     * @method SessionInterface drop
     * @param string $identifier
     * @return bool
     * 
     * Removes an identifier from $_SESSION
     */
    public function drop(string $identifier) : bool;

    /**
     * @method SessionInterface pop
     * @param string $identifier
     * @return mixed
     * 
     * Returns the value of a session and removes it.
     */
    public function pop(string $identifier);

    /**
     * @method SessionInterface dropMultiple
     * @param array $multiple
     * @return bool
     * 
     * Removes multiple keys from $_SESSION
     */
    public function dropMultiple(...$multiple) : bool;

    /**
     * @method SessionInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_SESSION with respective keys
     */
    public function pick(...$keys) : array;

    /**
     * @method SessionInterface except
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_SESSION except keys
     */
    public function except(...$keys) : array;

    /**
     * @method SessionInterface all
     * @return array
     * 
     * Returns all $_SESSION array
     */
    public function all() : array;

    /**
     * @method SessionInterface empty
     * @return bool
     * 
     * Returns true if $_SESSION is empty
     */
    public function empty() : bool;

    /**
     * @method SessionInterface clear
     * @return bool
     * 
     * Clears the $_SESSION array
     */
    public function clear() : bool;
}