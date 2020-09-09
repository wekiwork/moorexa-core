<?php
namespace Lightroom\Core\Interfaces;

/**
 * @package PrivateAutoloaderInterface 
 * 
 * Helper interface for private autoloader classes
 * With this, you can register a custom autoloader without having to use the spl_autoload_register function.
 * All you need to do is implement this interface on a class and register that class wih
 * FrameworkAutoloader::registerPrivateAutoloader(PrivateAutoloaderInterface $class)
 * 
 * @author fregatelab <fregatelab.com>
 * @author amadi ifeanyi <amadiify.com>
 */

interface PrivateAutoloaderInterface
{
    /**
     * @method PrivateAutoloaderInterface autoloaderRequested
     * This method would be called by the FrameworkAutoloader during runtime.
     * You can implement your logic in this method and must return a boolean (true | false)
     * @param string $class
     * @return bool
     */
    public function autoloaderRequested(string $class) : bool;
}