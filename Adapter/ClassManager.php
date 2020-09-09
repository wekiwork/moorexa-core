<?php
namespace Lightroom\Adapter;

/**
 * @package Class Manager
 * @author Amadi Ifeanyi <amadiify.com>
 * This package opens up a channel for building single instances of a class, attaching events/watcher to a class
 */
class ClassManager
{
    private static $instance = [];
    private static $assigns = [];
    private static $methods = [];
    public  static $BOOTMODE = [];
    public  static $channel = [];
    private static $classInstance = null;
    private static $pauseBootProcess = false;
    private        $classListening = null;
    private static $onLoadClosure = null;
    private static $awaitingPromise = [];
    public  static $named = [];
    private static $breakpointSaved = [];

    public static function assign(string $key, $data = null)
    {
        switch (self::has($key, 'assigns'))
        {
            case true:
                return self::$assigns[$key];
                break;

            case false:

                self::set($key, $data, 'assigns');

                return $data;
                break;
        }
    }

    public static function singleton_as(string $shortcut, string $className, $argument = [], $createInstance = true)
    {
        $instance = self::singleton($className, $argument, $createInstance);
        self::$named[$shortcut] = $instance;

        return $instance;
    }

    public static function singleton(string $className, $argument = null, $createInstance = true)
    {
        // set original classname
        $originalClassName = $className;

        // check if we already saved an instance of this class
        switch (self::has($className, 'instance')) :
        
            // yeah! so we return that instance
            case true:
                return self::$instance[$className];

            // oops! so we create an instance of this class and save it.
            case false:

                // already cached? return cached
                if (isset(self::$named[$className])) return self::$named[$className];

                // remove backward slash
                $className = ltrim($className, '\\');

                // we add a backward slash so we check outside this namespace
                $class = '\\' . $className; // eg : \Moorexa\Controller

                if (class_exists($class)) :
                
                    // create reflection object
                    $reflection = new \ReflectionClass($class);

                    // create instance
                    switch ($createInstance) :
                
                        case true:
                            // check if class has a constructor
                            if ($reflection->hasMethod('__construct')) :
                            
                                // @var array $const
                                $const = [];

                                $before = $argument;

                                // update arguments
                                $argument = (is_array($argument) ? [$argument] : [$argument]);

                                if (count($argument) > 0 && $before !== null) :

                                    // get arguments from constructor
                                    self::getParameters($class, '__construct', $const, $argument);

                                    // create instance
                                    $invoke = $reflection->newInstanceArgs($const);

                                else:

                                    $invoke = $reflection->newInstanceWithoutConstructor();

                                endif;

                                self::set($originalClassName, $invoke, 'instance');

                                return $invoke;

                            endif;
                        break;

                        case false:

                            $invoke = $reflection->newInstanceWithoutConstructor();

                            self::set($originalClassName, $invoke, 'instance');

                            return $invoke;
                    
                    endswitch;

                    // create instance without invoking arguments
                    $invoke = new $class;

                    self::set($originalClassName, $invoke, 'instance');

                    return $invoke;

                else :

                    throw new \Lightroom\Exceptions\ClassNotFound($className);
                
                endif;

                break;
        
        endswitch;
    }

    public static function method(string $definition, $returnData = null)
    {
        switch (self::has($definition, 'methods')) :
        
            case true:
                return self::$methods[$definition];

            case false:
                self::set($definition, $returnData, 'methods');
                return $returnData;

        endswitch;
    }

    public static function singleton_has(string $className)
    {
        if (isset(self::$instance[$className])) return true;

        return false;
    }

    public static function called(string $event, \closure $callback)
    {
        self::$channel[$event][] = $callback;
    }

    public static function on(string $event, \closure $callback)
    {
        return self::called($event, $callback);
    }

    private static function checkChannelAndCall(string $className)
    {
        // onload called.
        if (!is_null(self::$onLoadClosure)) call_user_func(self::$onLoadClosure, $className);

        // load channel
        if (isset(self::$channel[$className])) :
        
            foreach (self::$channel[$className] as $callback) :

                $instance = self::instance();
                $instance->classListening = $className;

                call_user_func($callback, $instance);

            endforeach;

        endif;
    }

    // get named
    public static function get(string $classShortName)
    {
        if (isset(self::$named[$classShortName])) return self::$named[$classShortName];

        return self::singleton($classShortName);
    }

    // has named
    public static function hasNamed(string $classShortName) : bool
    {
        if (isset(self::$named[$classShortName])) return true;

        return false;
    }

    // add breakpoint
    public static function addBreakPoint(string $breakpointIdentifier, \closure $callback)
    {
        self::$breakpointSaved[$breakpointIdentifier] = $callback;

        // call breakpoint
        self::lastMemory($breakpointIdentifier);
    }

    // load breakpoint
    public static function lastMemory(string $breakpointIdentifier)
    {
        $breakpoints = self::$breakpointSaved;

        if (isset($breakpoints[$breakpointIdentifier])) return call_user_func($breakpoints[$breakpointIdentifier]);

        return false;
    }

    private static function has(string $className, string $property) : bool
    {
        // get data
        $getFromProperty = self::${$property};

        // $hasdata
        $hasdata = false;

        if (isset($getFromProperty[$className])) :

            $hasdata = true;

        endif;

        // return bool
        return $hasdata;
    }

    private static function set(string $className, $data, string $property)
    {
        // push to array
        self::${$property}[$className] = &$data;

        // boot mode passed
        self::$BOOTMODE[$className] = true;

        // boot process paused ?
        if (self::$pauseBootProcess) :
        
            // boot mode paused
            self::$BOOTMODE[$className] = false;

        endif;

        // check channel
        self::checkChannelAndCall($className);
    }

    public static function instance()
    {
        if (is_null(self::$classInstance)) self::$classInstance = new self;

        return self::$classInstance;
    }

    // all calls
    public function __call(string $method, array $arguments)
    {
        // all dumps
        switch ($method) :
        
            case 'class':
                return isset(self::$instance[$this->classListening]) ? self::$instance[$this->classListening] : self::instance();

        endswitch;
    }

    public function stop()
    {
        // pause boot process
        self::$pauseBootProcess = true;

        // get keys
        $keys = array_keys(self::$BOOTMODE);

        // get index
        $index = array_flip($keys)[$this->classListening];

        // get class called after index
        $bootList = array_splice($keys, $index);

        // run a loop and stop all processes
        foreach ($bootList as $className) :

            // shut it down.
            self::$BOOTMODE[$className] = false;

        endforeach;
    }

    public function pause()
    {
        self::$BOOTMODE[$this->classListening] = false;
    }

    public static function onLoad(\closure $callback)
    {
        self::$onLoadClosure = $callback;
    }

    public function promise(string $promiseType, \closure $callback)
    {
        self::$awaitingPromise[$this->classListening][$promiseType] = $callback;
    }

    public static function methodGotCalled(string $method, $returnData)
    {
        if (isset(self::$awaitingPromise[$method])) :
        
            $promise = self::$awaitingPromise[$method];

            if (isset($promise['data'])) call_user_func($promise['data'], $returnData);

        endif;

        return $returnData;
    }

    // get class method parameters
    public static function getParameters($object, $method, &$bind=null, $unset = false, $url = [])
    {
        // create reflection class
        $ref = new \ReflectionClass($object);

        // check if method exists
        if ($ref->hasMethod($method)) :
        
            // get class method
            $classMethod = $ref->getMethod($method);

            // get method parameters
            $parameters = $classMethod->getParameters();

            // get url from $unset if it's not a boolean var
            if ($unset !== false) :
            
                if (is_array($unset)) :
                
                    $url = $unset;
                
                else:
                
                    // unset $url
                    if (is_array($url) && is_numeric($unset)) unset($url[$unset]);

                endif;

            endif;

            // @var array $newArray
            $newParameters = [];

            if (count($parameters) > 0) :
            
                foreach ($parameters as $index => $parameter) :
                
                    // update new parameters
                    $newParameters[$index][] = $parameter->name;

                    // get reflection parameters
                    $ref = new \ReflectionParameter([$object, $method], $index);

                    // Get class
                    $class = $ref->getClass();

                    if ($class !== null) :
                    
                        if ($class->isInstantiable()) : 

                            // using reflection 
                            $reflection = new \ReflectionClass($class->name);

                            // push class instance without constructor
                            $newParameters[$index][] = $reflection->newInstanceWithoutConstructor();

                            // clean up
                            unset($reflection);

                        endif;
                    
                    else:
                    
                        if ($ref->isDefaultValueAvailable()) :

                             $newParameters[$index][] = $ref->getDefaultValue();

                        else:

                            $newParameters[$index][] = null;

                        endif;

                    endif;

                endforeach;

            endif;

            //@var array $pushed
            $pushed = [];

            // check new paramaters
            foreach ($newParameters as $index => $parameter) :
            
                if (isset($parameter[1])) :
                
                    if (is_object($parameter[1])) :
                    
                        // push parameter
                        $pushed[$index] = $parameter[1];

                        // remove index
                        unset($newParameters[$index]);
                    
                    else:
                    
                        $pushed[$index] = null;

                    endif;
                
                else:
                
                    $pushed[$index] = null;

                endif;

            endforeach;

            $values = array_values($newParameters);
            $index = 0;

            foreach($pushed as $parameter_index => $value) :
            
                if ($value == null) :
                
                    if (isset($values[$index])) :
                    
                        // get parameter
                        $parameter = isset($values[$index][1]) ? $values[$index][1] : null;

                        // set parameter at this index from url
                        if (isset($url[$index])) $pushed[$parameter_index] = $url[$index];

                        // set from parameter
                        if (!is_null($parameter)) :
                        
                            $pushed[$parameter_index] = $parameter;

                            if (isset($url[$index])) $pushed[$parameter_index] = $url[$index];

                        endif;

                    endif;

                    $index++;

                endif;

            endforeach;

            $bind = $pushed;

        endif;

        // update bind
        $bind = is_null($bind) ? [] : $bind;
    }

    // check if a class is avaliable
    public static function ifAvailable(string $className, $returnData)
    {
        // when avaliable
        if (class_exists($className)) :
            
            if (is_string($returnData)) return $returnData;

            // closure
            if (is_callable($returnData)) return call_user_func($returnData, $className);

        endif;

        // when not avaliable
        return Errors\ClassNotAvaliable::class;
    }
}
