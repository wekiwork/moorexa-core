<?php
namespace Lightroom\Requests\Interfaces;

/**
 * @package Files Interface
 * @author Amadi Ifeanyi
 * 
 * This provides a quick abstraction for $_FILES requests
 */
interface FilesInterface
{
    /**
     * @method FilesInterface has
     * @param string $key
     * @return bool
     * 
     * Checks if $_FILES has a key
     */
    public function has(string $key) : bool;

    /**
     * @method FilesInterface hasMultiple
     * @param array $multiple
     * @return bool
     * 
     * Checks if $_FILES has multiple keys
     */
    public function hasMultiple(...$multiple) : bool;

    /**
     * @method FilesInterface get
     * @param string $key
     * @return mixed
     * 
     * Gets a value from $_FILES with a $key
     */
    public function get(string $key);

    /**
     * @method FilesInterface drop
     * @param string $key
     * @return bool
     * 
     * Removes a key from $_FILES
     */
    public function drop(string $key) : bool;

    /**
     * @method FilesInterface dropMultiple
     * @param array $multiple
     * @return bool
     * 
     * Removes multiple keys from $_FILES
     */
    public function dropMultiple(...$multiple) : bool;

    /**
     * @method FilesInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple array from $_FILES with respective keys
     */
    public function pick(...$keys) : array;
    
    /**
     * @method FilesInterface except
     * @param array $keys 
     * @return array
     */
    public function except(...$keys) : array;

    /**
     * @method FilesInterface all
     * @return array
     * 
     * Returns aLL $_FILES
     */
    public function all() : array;

    /**
     * @method FilesInterface empty
     * @return bool
     * 
     * Returns true if $_FILES is empty
     */
    public function empty() : bool;

    /**
     * @method FilesInterface moveTo
     * @param string $destination
     * @return bool
     * 
     * Moves a file into a directory
     */
    public function moveTo(string $destination) : bool;

    /**
     * @method FilesInterface clear
     * @return bool
     * 
     * Clears the $_FILES array
     */
    public function clear() : bool;
}