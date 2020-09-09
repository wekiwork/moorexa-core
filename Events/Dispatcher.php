<?php
namespace Lightroom\Events;

use Lightroom\Adapter\ClassManager;
/**
 * @package Dispatcher
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Dispatcher
{
    /**
     * @var array $eventsDispatched
     */
    private static $eventsDispatched = [];

    /**
     * @method Dispatcher eventDispatched
     * @param string $classAlaise
     * @param string $eventMethod
     * @return bool
     */
    public static function eventDispatched(string $classAlaise, string $eventMethod) : bool 
    {
        return (isset(self::$eventsDispatched[$classAlaise . '.' . $eventMethod]) ? true : false);
    }

    /**
     * @method Dispatcher eventsDispatched
     * @param string $classAlaise
     * @return array
     */
    public static function eventsDispatched(string $classAlaise) : array 
    {
        //@var array $events
        $events = [];

        // get all events dispatched
        foreach (self::$eventsDispatched as $eventMethod => $arguments) :

            // get all methods
            if (preg_match("/^($classAlaise)[.](.*+)/", $eventMethod, $match)) :

                // add event
                $events[$match[2]] = $arguments;

            endif;

        endforeach;

        // return array
        return $events;
    }

    /**
     * @method Dispatcher trigger event
     * @param string $classAlaise
     * @param array $arguments
     * @return void  
     */
    public static function __callStatic(string $classAlaise, array $arguments) : void 
    {
        // throw exception
        if (!isset($arguments[0])) throw new \Exception('Event method missing for dispatcher #'.$classAlaise);

        // get event method
        $eventMethod = $arguments[0];

        // add trigger
        self::$eventsDispatched[$classAlaise . '.' . $eventMethod] = array_splice($arguments, 1);

        // call all events listening
        self::callEventWaiting($classAlaise, $eventMethod);
    }

    /**
     * @method Dispatcher callEventWaiting
     * @param string $classAlaise
     * @param string $eventMethod
     * @return void 
     */
    public static function callEventWaiting(string $classAlaise, string $eventMethod) : void 
    {
        // get arguments
        $arguments = self::$eventsDispatched[$classAlaise . '.' . $eventMethod];

        // get attached event
        $attachedEvent = AttachEvent::getEventAttached($classAlaise);

        // get the event class
        $eventClass = ClassManager::singleton($attachedEvent['event-class']);

        // @var array $eventWaiting
        $eventWaiting = Listener::getEventsWaiting();

        // @var array $closureWaiting
        $closureWaiting = Listener::getEventsWaitingOnClosure($classAlaise);

        // call method from class
        if (method_exists($eventClass, $eventMethod)) :

            // @var array $argumentsNew
            $argumentsNew = [];

            // get parameters
            ClassManager::getParameters($eventClass, $eventMethod, $argumentsNew, $arguments);

            // call class method
            $returnVal = call_user_func_array([$eventClass, $eventMethod], $argumentsNew);

            // load closure
            EventHelpers::loadClosure($argumentsNew, $returnVal);

        endif;

        // call event waiting
        if (isset($eventWaiting[$classAlaise . '.' . $eventMethod])) :

            // get event
            $event = $eventWaiting[$classAlaise . '.' . $eventMethod];

            // check event has a closure
            if (isset($event[0]) && is_callable($event[0])) :

                // closure
                $closure = $event[0];

                // call closure
                $returnVal = call_user_func_array($closure->bindTo($eventClass, \get_class($eventClass)), $arguments);

                // load closure
                EventHelpers::loadClosure($arguments, $returnVal);
                
            endif;

        endif;

        // call closure events for class
        if (count($closureWaiting) > 0) :

            // @var array
            $closureArgument = array_merge([$eventMethod], $arguments);
            foreach ($closureWaiting as $closure) :
                $returnVal = call_user_func_array($closure, $closureArgument);
                // load closure
                EventHelpers::loadClosure($closureArgument, $returnVal);
            endforeach;
                 

        endif;

        // check if callback exists in attached event
        if (isset($attachedEvent['callback'])) :

            // call closure
            $returnVal = call_user_func_array($attachedEvent['callback']->bindTo($eventClass, \get_class($eventClass)), $arguments);

            EventHelpers::loadClosure($arguments, $returnVal);

        endif;
    }
}