<?php
namespace Lightroom\Core;

use closure;
use Lightroom\Core\Interfaces\{
    FrameworkAutoloaderEvents, PrivateAutoloaderInterface
};
use Lightroom\Core\FrameworkAutoloaderPusher;
use Lightroom\Exceptions\AutoloadRegisterException;

/**
 * @package Framwework autoloader
 * @author fregatelab <fregatelab.com>
 */

class FrameworkAutoloader
{
    /**
     * @var array registered pushers
     */
    public static $registeredPushers = [];

    /**
     * @var FrameworkAutoloader $namespaces
     */
    private static $namespaces = [];

    /**
     * @var FrameworkAutoloader $autoloader
     */
    private static $autoloader = [];

    /**
     * @var bool $autoloaderLevel
     * This signifies the level of importance for a registered autoloader file
     */
    private $autoloaderLevel = 1;

    /**
     * @var array $registeredPrivateAutoloader
     */
    private static $registeredPrivateAutoloader = [];

    /**
     * @method registerNamespace
     * @param array $namespaceArray
     * @return FrameworkAutoloader
     */
    public static function registerNamespace(array $namespaceArray) : FrameworkAutoloader
    {
        // merge with existing namespaces
        self::$namespaces = array_merge((array) self::$namespaces, $namespaceArray);

        // clean up
        unset($namespaceArray);

        // return class instance
        return new self;
    }

    /**
     * @method FrameworkAutoloader registerAutoloader method
     */
    public function registerAutoloader()
    {
        // invoke this class
        spl_autoload_register([static::class, 'autoloader']);
        
        return $this;
    }

    /**
     * @method FrameworkAutoloader classAutoload
     * This method checks to know if class has been autoload
     * @param string $className
     * @return bool
     */
    public static function classAutoload(string $className)
    {
        // @var $autoload
        $autoload = false;

        // check $autoloadArray
        if (isset(array_flip((array) self::$autoloader)[$className])) :
        
            $autoload = true;
        
        endif;

        // clean up
        unset($className);

        // return bool
        return $autoload;
    }

    /**
     * @method FrameworkAutoloader autoloader method
     * we listen for class requests
     * @param string $className
     */
    public static function autoloader(string $className)
    {
        /**
         * @method FrameworkAutoloader checkRegisteredNamespaces
         */
        self::collect($className, [static::class, 'checkRegisteredNamespaces']);

        /**
         * @method FrameworkAutoloader addExtensionAndCheckFileExists
         */
        self::collect($className, [static::class, 'addExtensionAndCheckFileExists']);

        /**
         * @method FrameworkAutoloader checkPrivateAutoloader
         */
        self::collect($className, [static::class, 'checkPrivateAutoloader']);
    }

    /**
     * @method FrameworkAutoloader checkRegisteredNamespaces method
     * check class in registered namespace
     * @param string $className
     * @return bool
     */
    private static function checkRegisteredNamespaces(string $className) : bool
    {
        // @var pathFound
        $pathFound = false;

        // check size
        if (count((array) self::$namespaces) > 0) 

            // closure function to include file
            $includeFile = function(string $classWithBasePath, $other = null) use (&$pathFound, &$className)
            {
                // check if file exists, and break out of loop if yes
                if (file_exists($classWithBasePath) || file_exists($other)) :

                    // include file
                    include_once (file_exists($classWithBasePath) ? $classWithBasePath : $other);

                    // does class exists
                    if (class_exists($className) || trait_exists($className) || interface_exists($className)) $pathFound = true;

                endif;
            };

            // we use foreach loop
            foreach (self::$namespaces as $namespace => $basePath) :
            
                // get namespace size
                $sizeOfNameSpace = strlen($namespace);

                // compare string with size
                if (strncmp($className, $namespace, $sizeOfNameSpace) === 0) :

                    // @var string $file
                    $file = '/' . substr(str_replace('\\', '/', $className), $sizeOfNameSpace) . '.php';

                    if (is_array($basePath)) :

                        // run loop
                        foreach ($basePath as $basePathFolder => $replacePath) :

                            // replace path
                            if (is_string($basePathFolder) && is_string($replacePath)) $file = str_replace($replacePath, '', $file);

                            // manage switch
                            if (is_numeric($basePathFolder)) $basePathFolder = $replacePath;
                            
                            // include file
                            if ($basePathFolder !== null) $includeFile($basePathFolder . $file, $basePathFolder . '/' . basename($file));
                            
                            // break loop
                            if ($pathFound) break;

                        endforeach;

                    else:

                        // convert \ to / and get base path
                        $includeFile($basePath . $file);

                    endif;

                    // break loop
                    if ($pathFound) break;
                    
                endif;
                
            endforeach;

        
        // clean up
        unset($sizeOfNameSpace, $namespace, $classWithBasePath, $basePath);

        // return path found
        return $pathFound;
    }

    /**
     * @method FrameworkAutoloader addExtensionAndCheckFileExists method
     * check class with the php extension
     * @param string $className
     * @return bool
     */
    private static function addExtensionAndCheckFileExists(string $className) : bool
    {
        // @var pathFound
        $pathFound = false;

        // convert \ to / and get class path
        $classWithExtension = str_replace('\\', '/', $className) . '.php';
        
        // check if file exits
        if (file_exists($classWithExtension)) :
        
            // path found
            $pathFound = true;

            // include file
            include_once $classWithExtension;

        endif;

        // clean up
        unset($classWithExtension, $className);

        // return path found
        return $pathFound;
    }

    /**
     * @method FrameworkAutoloader checkPrivateAutoloader
     * Check from $registeredPrivateAutoloader for classes that implements PrivateAutoloaderInterface and trigger autoloaderRequested method
     * The method will return a boolean that will be passed to the collect method.
     * @param string $className
     * @return bool
     */
    private static function checkPrivateAutoloader(string $className) : bool
    {
        // autoload passed
        $autoload = false;

        if (count(self::$registeredPrivateAutoloader) > 0) :

            // run a foreach loop and break out if $class returns true
            foreach (self::$registeredPrivateAutoloader as $class) :

                // call autoloaderRequested
                if ($class->autoloaderRequested($className) && class_exists($className)) :

                    $autoload = true;
                    break;

                endif;

            endforeach;
            
        endif;

        return $autoload;
    }


    /**
     * @method FrameworkAutoloader registerPrivateAutoloader
     * Register a class that implements PrivateAutoloaderInterface for private autoload
     * @param PrivateAutoloaderInterface $class
     */
    public static function registerPrivateAutoloader(PrivateAutoloaderInterface $class)
    {
        self::$registeredPrivateAutoloader[] = $class;
    }

    /**
     * @method FrameworkAutoloader collector for autoloader
     * This method would execute for other channels if no match has been found
     * @param string $className
     * @param array $callableArray
     */
    private static function collect(string $className, array $callableArray)
    {
        // check if class has been loaded
        if (self::classAutoload($className)) :

            // stop execution
            return;

        endif;
        

        // call callable array
        call_user_func(function() use ($className, $callableArray)
        {
            // load class method and save to $autoload if found
            if (call_user_func($callableArray, $className)) :
        
                // save to array
                self::$autoloader[] = $className;
                
            endif;

            // clean up
            unset($className, $callableArray);
        });
    }

    /**
     * @method FrameworkAutoloader secondary Autoloader
     * @param closure $callbackFunction
     * @return FrameworkAutoloader
     */
    public function secondaryAutoloader(closure $callbackFunction) : FrameworkAutoloader
    {
        // set autoload level to 0
        $this->autoloaderLevel = 0;

        // get autoloader
        call_user_func($callbackFunction->bindTo($this, static::class));

        // clean up
        unset($callbackFunction);

        // return class instance
        return $this;
    }

    /**
     * @method FrameworkAutoloader primary Autoloader
     * @param closure $callbackFunction
     * @return FrameworkAutoloader
     */
    public function primaryAutoloader(closure $callbackFunction) : FrameworkAutoloader
    {
        // set autoload level to 1
        $this->autoloaderLevel = 1;

        // get autoloader
        call_user_func($callbackFunction->bindTo($this, static::class));

        // clean up
        unset($callbackFunction);

        // return class instance
        return $this;
    }

    /**
     * @method FrameworkAutoloader autoloadRegister
     * @param string $filepath
     * @throws AutoloadRegisterException
     */
    public function autoloadRegister(string $filepath)
    {
        if (file_exists($filepath)) :

            // include file
            include_once $filepath;
        
        else:
        
            if ($this->autoloaderLevel > 0) :

                // throw AutoloadRegisterException exception
                throw new AutoloadRegisterException('Could not autoload from {'.$filepath.'}, autoloader wasn\'t found.');
            
            endif;
            
        endif;

        // clean up
        unset($filepath);
    }

    /**
     * @method FrameworkAutoloader registerPusherEvent
     * Call classes that registered with the autoloader pusher.
     */ 
    public function registerPusherEvent()
    {
        // only catch what could not be autoload.
        spl_autoload_register(function($class)
        {
            // call autoloadFailed
            foreach (self::$registeredPushers as &$pusher) : 

                // call from $pusher class
                $pusher->autoloadFailed($class);

            endforeach;

            // clean up
            unset($class, $pusher);
        });
    }
}