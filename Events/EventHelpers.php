<?php
namespace Lightroom\Events;
/**
 * @package Event Helpers
 * @author Amadi Ifeanyi
 */
class EventHelpers
{
    /**
     * @var $basicClass
     */
    private static $basicClass = null;

    /**
     * @method EventHelpers loadAll
     * @return array
     */
    public static function loadAll() : array 
    {
        return [
            
            // load basic class without preloading event class
            'basic' => function ()
            {
                return self::loadBasic();
            },

            // load shared class with preoloaded event class
            'shared' => function(string $eventClass)
            {
                return self::loadShared($eventClass);
            }
        ];
    }

    /**
     * @method EventHelpers loadBasic
     * @return class 
     */
    private static function loadBasic()
    {
        // not class if not null
        if (self::$basicClass !== null) return self::$basicClass;

        // load class if null
        self::$basicClass = new class()
        {
            // attach event
            public function attach(...$arguments)
            { 
                return call_user_func_array([AttachEvent::class, 'attach'], $arguments); 
            }

            // listen for event
            public function on(string $eventClass, ...$arguments) 
            { 
                return call_user_func_array([Listener::class, $eventClass], $arguments); 
            }

            // emit event
            public function emit(string $eventClass, ...$arguments) 
            { 
                
                // load disptacher
                return call_user_func_array([Dispatcher::class, $eventClass], $arguments); 
            }

            // can emit
            public function canEmit(string $eventAndListener, $callback = null) : bool
            {
                // check if we can emit a class
                // @var boolean $canContinue
                $canContinue = false;

                // do we have events in $_ENV glob array ?
                if (isset($_ENV['events'])) :

                    // get the event class
                    $eventClass = substr($eventAndListener, 0, strpos($eventAndListener, '.'));

                    // get the event
                    $event = substr($eventAndListener, strpos($eventAndListener, '.')+1);

                    // check if we have the eventClass configured
                    if (isset($_ENV['events'][$eventClass])) :

                        // flip array values for keys
                        $eventList = array_flip($_ENV['events'][$eventClass]);

                        // check if we have event
                        if (isset($eventList[$event])) $canContinue = true;

                    endif;

                endif;

                // return boolean    
                return $canContinue;
            }
        };

        // return basic class
        return self::$basicClass;
    }

    /**
     * @method EventHelpers loadShared
     * @param string $eventClass
     * @return event
     */
    private static function loadShared(string $eventClass)
    {
        return new class($eventClass)
        {
            // @var string $eventClass
            private $eventClass = '';

            // load event class
            public function __construct(string $eventClass)
            {
                $this->eventClass = $eventClass;
            }

            // listen for event
            public function on(...$arguments)
            {
                return call_user_func_array([Listener::class, $this->eventClass], $arguments); 
            }

            // emit event
            public function emit(...$arguments)
            {
                return call_user_func_array([Dispatcher::class, $this->eventClass], $arguments); 
            }
        };
    } 

    /**
     * @method EventHelpers loadClosure
     * @param array $arguments
     * @param mixed $returnVal
     */
    public static function loadClosure(array $arguments, $returnVal)
    {
        // get the last element
        $lastElement = array_pop($arguments);

        // is closure
        if ($lastElement !== null && is_callable($lastElement)) :

            // call closure
            call_user_func($lastElement, $returnVal);

        endif;
    }
}