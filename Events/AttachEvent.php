<?php
namespace Lightroom\Events;

use Closure;
use Lightroom\Exceptions\ClassNotFound;
/**
 * @package AttachEvent
 * @author Amadi Ifeanyi <amadiify.com>
 */
class AttachEvent
{
    /**
     * @var array $eventAttached
     */
    private static $eventAttached = [];

    /**
     * @var AttachEvent $instance
     */
    private static $instance;

    /**
     * @var string $classAlaise
     */
    public $classAlaise = '';

    /**
     * @method AttachEvent attach
     * @param string $eventClass
     * @param string $classAlaise
     * @return AttachEvent
     */
    public static function attach(string $eventClass, string $classAlaise) : AttachEvent
    {
        // create instance
        if (self::$instance === null) self::$instance = new AttachEvent;

        // reset class alaise
        self::$instance->classAlaise = $classAlaise;

        // check if class exits
        if (!class_exists($eventClass)) throw new ClassNotFound($eventClass);

        // attach event
        self::$eventAttached[$classAlaise] = ['event-class' => $eventClass];

        // return instance
        return self::$instance;
    }

    /**
     * @method AttachEvent callback
     * @param Closure $callback
     * @return void
     * 
     * Attach a callback to attached event.
     */
    public function callback(Closure $callback) : void
    {
        self::$eventAttached[$this->classAlaise]['callback'] = $callback;
    }

    /**
     * @method AttachEvent eventAttached
     * @param string $classAlaise
     * @return bool
     */
    public static function eventAttached(string $classAlaise) : bool 
    {
        return (isset(self::$eventAttached[$classAlaise]) ? true : false);
    }

    /**
     * @method AttachEvent getEventAttached
     * @param string $classAlaise
     * @return array
     */
    public static function getEventAttached(string $classAlaise) : array 
    {
        if (self::eventAttached($classAlaise) === false) throw new \Exception('No event attached to "'.$classAlaise.'"');

        // return array 
        return self::$eventAttached[$classAlaise];
    }
}