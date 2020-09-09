<?php
namespace Lightroom\Core\Interfaces;

/**
 * @package FrameworkAutoloaderEvents for classes watching what's going on inside the framework autoloader
 * @author amadi ifeanyi <amadiify.com>
 * @author fregatelab <fregatelab.com>
 */
interface FrameworkAutoloaderEvents
{
    /**
     * @method FrameworkAutoloaderEvents autoloadFailed
     * Call this method when autoload fails.
     * registerPusherEvent() method must be called on FrameworkAutoloader for this to work.
     * @param string $className
     */
    public function autoloadFailed(string $className);

    /**
     * @method FrameworkAutoloaderEvents source
     *
     * set source class for autoloader event
     * @param string $className
     * @return FrameworkAutoloaderEvents
     */
    public function source(string $className) : FrameworkAutoloaderEvents;

    /**
     * @method FrameworkAutoloaderEvents register
     *
     * set source group for source class
     * @param array $sourceGroup
     * @return FrameworkAutoloaderEvents
     */
    public function register(array $sourceGroup) : FrameworkAutoloaderEvents;
}