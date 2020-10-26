<?php
namespace Lightroom\Adapter;

use Exception;
use Lightroom\Adapter\{ClassManager};
use Lightroom\Exceptions\{ClassNotFound, MethodNotFound, InterfaceNotFound};
use Lightroom\Adapter\Interfaces\ContainerInterface;
/**
 * @package Adapter Container
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Container
{
    /**
     * @var Container $instance
     */
    private static $instance;

    /**
     * @var array $registry
     */
    private static $registry = [];

    /**
     * @var array $injectors
     */
    private static $injectors = [];

    /**
     * @method Container register
     * @return Container
     * 
     * This method registers a list of classes
     */
    public static function register(array $registry) : Container
    {
        // register class
        self::$registry = array_unique(array_merge(self::$registry, $registry));

        // return instance
        return self::instance();
    }

    /**
     * @method Container instance
     * @return Container
     * 
     * This method returns the instance of container class
     */
    public static function instance() : Container
    {
        // get instance
        if (self::$instance == null) self::$instance = new Container;

        // return instance
        return self::$instance;
    }

    /**
     * @method Container fresh
     * @return mixed
     * 
     * This method returns a instance of a container class
     */
    public static function fresh(string $classPlaceholder, ...$arguments)
    {
        // check class placeholder
        if ($classPlaceholder != null) :

            // get class name
            $className = self::getClassName($classPlaceholder);

            // get reflection
            $reflection = new \ReflectionClass($className);

            //do we have arguments
            if (count($arguments) > 0) return $reflection->newInstanceArgs($arguments);

            // return instance without args
            return $reflection->newInstanceWithoutConstructor();

        endif;

        // return instance
        return self::$instance;
    }

    /**
     * @method Container load
     * @param string $classPlaceholder
     * @return mixed
     * @throws 
     * 
     * This method loads a registered class
     */
    public static function load(string $classPlaceholder)
    {
        // return a class
        return new class($classPlaceholder)
        {
            /**
             * @var string $classPlaceholder
             */
            private $classPlaceholder = '';

            /**
             * @method Container __construct
             * @param string $classPlaceholder
             */
            public function __construct(string $classPlaceholder) { $this->classPlaceholder = $classPlaceholder; }

            /**
             * @method Container __call
             * @param string $method
             * @param array $arguments
             * Load container call method 
             */
            public function __call(string $method, array $arguments)
            {
                // build new argument
                $newArgument = array_merge([$this->classPlaceholder, $method], $arguments);

                // load call method
                return call_user_func_array([Container::instance(), 'call'], $newArgument);
            }

            /**
             * @method Container __set
             * @param string $property
             * @param mixed $value
             * @return void
             */
            public function __set(string $property, $value)
            {
                // load set method
                call_user_func_array([Container::instance(), 'set'], [
                    $this->classPlaceholder,
                    $property,
                    $value
                ]);
            }

            public function __get(string $property)
            {
                // load get method
                return call_user_func_array([Container::instance(), 'get'], [
                    $this->classPlaceholder, $property]
                );
            }

            /**
             * @method Container instance
             * @param array $arguments
             * @return mixed
             */
            public function instance(...$arguments)
            {
                // get class name
                $className = self::getClassName($this->classPlaceholder);

                // return class instance
                if (is_object($className)) return $className;

                // load singleton
                if (count($arguments) == 0) return ClassManager::singleton($className);

                // load new instance
                $reflection = new \ReflectionClass($className);

                // return instance with arguments
                return $reflection->newInstanceArgs($arguments);
            }
        };
    }

    /**
     * @method Container get
     * @param string $classPlaceholder
     * @param string $property
     * @return mixed
     */
    public function get(string $classPlaceholder, string $property)
    {
        // get class name
        $className = self::getClassName($classPlaceholder);

        // get reflection
        $reflection = new \ReflectionClass($className);

        // check for property
        if ($reflection->hasProperty($property)) :

            // get property
            $reflectionProperty = $reflection->getProperty($property);

            // continue if public
            if ($reflectionProperty->isPublic()) :

                // get static value
                if ($reflectionProperty->isStatic()) return $reflectionProperty->getValue();

                // get class instance
                $instance = is_string($className) ? ClassManager::singleton($className) : $className;

                // return value
                return $instance->{$property};

            endif;

        endif;
    }

    /**
     * @method Container set
     * @param string $classPlaceholder
     * @param string $property
     * @param mixed $value
     * @return mixed
     */
    public function set(string $classPlaceholder, string $property, $value) 
    {
        // get class name
        $className = self::getClassName($classPlaceholder);

        // get reflection
        $reflection = new \ReflectionClass($className);

        // check for property
        if ($reflection->hasProperty($property)) :

            // get property
            $reflectionProperty = $reflection->getProperty($property);

            if ($reflectionProperty->isPublic()) :

                // if is static value
                if ($reflectionProperty->isStatic()) :

                    // set value
                    $reflectionProperty->setValue($value);

                else:

                    // get class instance
                    $instance = is_string($className) ? ClassManager::singleton($className) : $className;

                    // set value
                    $instance->{$property} = $value;

                endif;

            endif;

        endif;

        // rreturn value
        return $value;
    }

    /**
     * @method Container add
     * @param string $classPlaceholder
     * @param mixed $class 
     * @return Container
     */
    public function add(string $classPlaceholder, $class) : Container
    {
        // add to registry
        self::$registry[$classPlaceholder] = $class;

        // load processor method
        self::loadProcessorMethod('registryCalled', $this);

        // return instance
        return $this;
    }

    /**
     * @method Container has
     * @param string $classPlaceholder
     * @return bool
     */
    public function has(string $classPlaceholder) : bool
    {
        // return bool
        return isset(self::$registry[$classPlaceholder]) ? true : false;
    }

    /**
     * @method Container drop
     * @param string $classPlaceholder
     * @return Container
     */
    public function drop(string $classPlaceholder) : Container
    {
        // get className
        $className = isset(self::$registry[$classPlaceholder]) ?  self::$registry[$classPlaceholder] : null;

        // remove from registry
        if ($className != null) :
            
            // remove class from registry
            unset(self::$registry[$classPlaceholder]);

            // load processor method
            self::loadProcessorMethod('classDropped', $className);

        endif;

        // return instance
        return $this;
    }

    /**
     * @method Container call
     * @param string $classPlaceholder
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function call(string $classPlaceholder, string $method, ...$arguments)
    {
        // get class name
        $className = self::getClassName($classPlaceholder);

        // load processor method
        if(is_string($className)) self::loadProcessorMethod('classCalled', $className);

        // get reflection
        $reflection = new \ReflectionClass($className);

        // check for method
        if (!$reflection->hasMethod($method) && is_string($className)) throw new MethodNotFound($className, $method);

        // get method
        $reflectionMethod = $reflection->getMethod($method);

        // load static method
        if ($reflectionMethod->isStatic()) return call_user_func_array([$className, $method], $arguments);

        // load non static method
        $instance = is_string($className) ? ClassManager::singleton($className) : $className;

        // return method
        return call_user_func_array([$instance, $method], $arguments);
    }

    /**
     * @method Container all
     * @return array
     * 
     * Returns all registered classes
     */
    public function all() : array 
    {
        return self::$registry;
    }

    /**
     * @method Container inject
     * @param array $processor
     * @return void
     * 
     * Inject a container processor
     */
    public function inject(array $processors) : void 
    {
        // try to inject processor, but be sure class exists
        foreach ($processors as $processor) :

            // check if class exists
            if (!class_exists($processor)) throw new ClassNotFound($processor);

            // get reflection of this class
            $reflection = new \ReflectionClass($processor);

            // throw error if class doesn't implement our interface
            if (!$reflection->implementsInterface(ContainerInterface::class)) throw new InterfaceNotFound($processor, ContainerInterface::class);

            // now add to injectors
            self::$injectors[] = $processor;

            // load registryCalled method
            call_user_func([$processor, 'registryCalled'], $this);

        endforeach;
    }

    /**
     * @method Container getClassName
     * @param string $classPlaceholder
     * @return mixed
     * @throws Exception
     * @throws ClassNotFound
     */
    private static function getClassName(string $classPlaceholder)
    {
        // check if registered, throw exception
        if (!isset(self::$registry[$classPlaceholder])) throw new Exception('Container could not load class for '. $classPlaceholder.'. It just was not found.');

        // get class from placeholder
        $className = self::$registry[$classPlaceholder];

        // throw exception if class could not be found
        if (is_string($className) && !class_exists($className)) throw new ClassNotFound($className);

        // return class name
        return $className;
    }

    /**
     * @method Container loadProcessorMethod
     * @param string $method
     * @param mixed $argument
     * @return void
     */
    private static function loadProcessorMethod(string $method, $argument) : void 
    {
        // get all $injectors
        $injectors = self::$injectors;
        
        // continue if we have something
        if (count($injectors) > 0) :

            // load processors
            foreach ($injectors as $processor) call_user_func([$processor, $method], $argument);

        endif;
    }
}