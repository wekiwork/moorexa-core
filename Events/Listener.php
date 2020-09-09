<?php
namespace Lightroom\Events;

/**
 * @package Listener
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Listener
{
    /**
     * @var array $eventWaiting
     */
    private static $eventWaiting = [];

    /**
     * @var array $eventWaitingClosure
     */
    private static $eventWaitingClosure = [];

    /**
     * @method Listener __callStatic
     * @param string $classAlaise
     * @param array $arguments
     * @return void
     */
    public static function __callStatic(string $classAlaise, array $arguments) : void
    {
        self::__listenForEvent($classAlaise, $arguments);
    }

    /**
     * @method Listener on
     * @param array ...$arguments
     * @return void
     */ 
    public static function on(...$arguments) : void
    {
        // get classAlaise
        $classAlaise = $arguments[0];

        // split classAlaise and get class method
        $classArray = explode('.', $classAlaise);

        // get class method and alaise
        list($classAlaise, $classMethod) = $classArray;
        
        // get arguments
        $arguments = array_splice($arguments, 1);

        // move method to the begining of arguments
        array_unshift($arguments, $classMethod);

        // listen for event
        self::__listenForEvent($classAlaise, $arguments);
    } 

    /**
     * @method Listener getEventsWaiting
     * @return array
     */
    public static function getEventsWaiting() : array 
    {
        return self::$eventWaiting;
    }

    /**
     * @method Listener getEventsWaitingOnClosure
     * @return array
     */
    public static function getEventsWaitingOnClosure(string $identifier) : array 
    {
        return isset(self::$eventWaitingClosure[$identifier]) ? self::$eventWaitingClosure[$identifier] : [];
    }

    /**
     * @method Listener __listenForEvent
     * @param string $classAlaise
     * @param array $arguments
     * @return void
     */
    private static function __listenForEvent(string $classAlaise, array $arguments) : void 
    {
        // has event
        if (AttachEvent::eventAttached($classAlaise)) :

            // get event method
            $eventMethod = $arguments[0];

            if (!is_object($eventMethod)) :

                // get arguments
                $arguments = array_splice($arguments, 1); 

                if (is_array($eventMethod)) :

                    foreach ($eventMethod as $event):

                        // add to waiting list 
                        self::addToWaitingList($classAlaise, $event, $arguments);

                    endforeach;

                else:

                    // add to waiting list 
                    self::addToWaitingList($classAlaise, $eventMethod, $arguments);

                endif;

            else:

                // add to closure
                self::$eventWaitingClosure[$classAlaise][] = $eventMethod;

                // check for event dispatched
                $dispatched = Dispatcher::eventsDispatched($classAlaise);

                if (count($dispatched) > 0) :

                    // load closure
                    foreach ($dispatched as $event => $arguments) call_user_func_array($eventMethod, array_merge([$event], $arguments));

                endif;

            endif;

        else:

            // @var string $eventMethod
            $eventMethod = $arguments[0];

            // get arguments
            $arguments = array_splice($arguments, 1); 

            // add to waiting list
            self::addToWaitingList($classAlaise, $eventMethod, $arguments);

        endif;
    }

    /**
     * @method Listener addToWaitingList
     * @param string $classAlaise
     * @param string $method
     * @param array $arguments
     * @return void
     */
    private static function addToWaitingList(string $classAlaise, string $method, array $arguments) : void 
    {
        self::$eventWaiting[$classAlaise . '.' . $method] = $arguments;

        // check if dispatched
        if (Dispatcher::eventDispatched($classAlaise, $method)) :

            // call event waiting 
            Dispatcher::callEventWaiting($classAlaise, $method);

        endif;
    }
}