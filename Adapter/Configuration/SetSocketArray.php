<?php
namespace Lightroom\Adapter\Configuration;

use Lightroom\Adapter\Configuration\Interfaces\SetSocketArrayInterface;

/**
 * @package Setsocketarray trait
 */
trait SetSocketArray
{
    // get current class
    public $socketArrayClass = '';

    // get current method
    private $socketArrayMethod = '';

    // @var array $SocketArrayDump
    private $SocketArrayDump = [];

    // get current index
    private $socketArrayIndex = 0;

    /**
     * @method SetSocketArray setSocketClass
     * set socket class
     * @param string $class
     * @return SetSocketArrayInterface
     */
    public function setSocketClass(string $class) : SetSocketArrayInterface
    {
        // create class
        $classObject = new class() implements SetSocketArrayInterface
        { 
            use SetSocketArray; 
        };

        // set socket class
        $classObject->socketArrayClass = $class;

        // clean up
        unset($class);

        // return class instance
        return $classObject;
    }

    /**
     * @method SetSocketArray setSocketMethod
     * set socket class method
     * @param string $method
     * @return SetSocketArrayInterface
     */
    public function setSocketMethod(string $method) : SetSocketArrayInterface
    {
        $this->socketArrayMethod = $method;

        // save now 
        $this->SocketArrayDump[] = ['class' => $this->socketArrayClass, 'method' => $this->socketArrayMethod];

        // clean up
        unset($method);

        // return class instance
        return $this;
    }   

    /**
     * @method SetSocketArray getSocketClass
     * get socket class
     */
    public function getSocketClass() : string
    {
        return $this->SocketArrayDump[$this->socketArrayIndex]['class'];
    }

    /**
     * @method SetSocketArray getSocketMethod
     * get socket class method
     */
    public function getSocketMethod() : string
    {
        $method = $this->SocketArrayDump[$this->socketArrayIndex]['method'];

        // increment index
        $this->socketArrayIndex++;

        // return method
        return $method;
    }
}