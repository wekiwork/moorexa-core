<?php
namespace Lightroom\Core;

use Lightroom\Core\Interfaces\FrameworkAutoloaderEvents;

/**
 * @package FrameworkAutoloaderPusher
 * @author amadi ifeanyi <amadiify.com>
 */
trait FrameworkAutoloaderPusher
{
    /**
     * @method FrameworkAutoloaderPusher registerPusher
     * Register a class that implements FrameworkAutoloaderEvents interface
     * @param string $className
     * @param FrameworkAutoloaderEvents $classInstance
     */
    private static function registerPusher(string $className, FrameworkAutoloaderEvents $classInstance)
    {
        // register pusher
        FrameworkAutoloader::$registeredPushers[$className] = $classInstance;

        // clean up
        unset($className, $classInstance);
    }
}