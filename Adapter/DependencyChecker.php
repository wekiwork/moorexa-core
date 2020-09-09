<?php
namespace Lightroom\Adapter;

use closure;
use Lightroom\Core\{
    Interfaces\FrameworkAutoloaderEvents, FrameworkAutoloaderPusher
};
use Lightroom\Exceptions\DependencyFailedForClass;

/**
 * @package DependencyChecker
 */
class DependencyChecker implements FrameworkAutoloaderEvents
{
    use FrameworkAutoloaderPusher;

    /**
     * @var string sourcename
     */
    private $sourcename = '';

    /**
     * @var array dependencies
     */
    private static $dependencies = [];

    /**
     * @method DependencyChecker forClasses
     * @param closure $closure
     */
    public function forClasses(closure $closure)
    {
        // register pusher
        $this->registerPusher(static::class, $this);

        // bind current object to closure
        $closure = $closure->bindTo($this, static::class);

        // call closure
        call_user_func($closure);
    }

    /**
     * @method DependencyChecker autoloadFailed
     * Call this method when autoload fails.
     * registerPusherEvent() method must be called on FrameworkAutoloader for this to work.
     * @param string $className
     * @throws DependencyFailedForClass
     */
    public function autoloadFailed(string $className)
    {
       if (isset(self::$dependencies[$className])) :

           // get source group
           $sourceGroup = self::$dependencies[$className];

           foreach ($sourceGroup as $sourcegroup) :

                // get class name and dependency error
                list($class, $dependencyError) = $sourcegroup;

                // dependency exception
                throw new DependencyFailedForClass($class, $className, $dependencyError);
                
           endforeach;

           // clean up
           unset($sourceGroup, $class, $dependencyError);

       endif;
    }

    /**
     * @method DependencyChecker source
     *
     * set source class for autoloader event
     * @param string $className
     * @return FrameworkAutoloaderEvents
     */
    public function source(string $className) : FrameworkAutoloaderEvents
    {
        $this->sourcename = $className;

        return $this;
    }

    /**
     * @method DependencyChecker register
     *
     * set source group for source class
     * @param array $sourceGroup
     * @return FrameworkAutoloaderEvents
     */
    public function register(array $sourceGroup) : FrameworkAutoloaderEvents
    {
        if ($this->sourcename != '') :

            foreach ($sourceGroup as $dependencyClass => &$dependencyError) :

                // create hash
                $hashName = md5($this->sourcename . $dependencyError);

                // save dependencies
                self::$dependencies[$dependencyClass][$hashName] = [$this->sourcename, $dependencyError];

                // clean up
                unset($dependencyError, $hashName);

            endforeach;

            // clean up
            unset($sourceGroup, $dependencyError, $dependencyClass);

        endif;

        // remove source name
        $this->sourcename = '';
        
        return $this;
    }
}