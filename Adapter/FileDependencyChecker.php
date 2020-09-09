<?php
namespace Lightroom\Adapter;

use Exception;
use Lightroom\Core\{
    Interfaces\FrameworkAutoloaderEvents, FrameworkAutoloaderPusher, 
    ThrowableExceptionManager
};
use Lightroom\Exceptions\{
    DependencyFailedForClass, DependencyFailedForVariable, 
    DependencyFailedForFunction, Interfaces\ThrowableExceptionInterface
};

/**
 * @package FileDependencyChecker
 * 
 * This package requires FrameworkAutoloaderPusher for class types
 * 
 * @author amadi ifeanyi <amadiify.com>
 */
class FileDependencyChecker implements FrameworkAutoloaderEvents, ThrowableExceptionInterface
{
    use FrameworkAutoloaderPusher, ThrowableExceptionManager;

    /**
     * @var string $filepath
     */
    private $filepath = '';

    /**
     * @var array $fileCheckerList
     */
    private static $fileCheckerList = ['class' => [], 'variable' => [], 'function' => []];

    /**
     * @var string $globalKeyword
     */
    public static $globalKeyword = '*';

    /**
     * @method FileDependencyChecker constructor
     * register this class autoloader pusher
     */
    public function __construct()
    {
        // register pusher
        $this->registerPusher(static::class, $this);

        // register throwable
        $this->registerThrowable($this);
    }

    /**
     * @method FileDependencyChecker path
     * @param string $filepath
     * @return FileDependencyChecker instance
     */
    public function path(string $filepath) : FileDependencyChecker
    {
        $this->filepath = $filepath;

        return $this;
    }

    /**
     * @method FileDependencyChecker dependency
     * @param array $configuration
     * @return string path
     */
    public function dependency(array $configuration) : string
    {
        // manage dependency checks
        foreach ($configuration as $dependencyLevel => $dependencies) :

            switch ($dependencyLevel) :

                // add for class
                case 'class':

                    // add 
                    self::$fileCheckerList['class'][$this->filepath] = $dependencies;

                break;

                // add for variable
                case 'variable':

                    // add 
                    self::$fileCheckerList['variable'][$this->filepath] = $dependencies;

                break;  

                // add for function
                case 'function':

                    // add 
                    self::$fileCheckerList['function'][$this->filepath] = $dependencies;

                break;  

            endswitch;

        endforeach;

        // return file path
        return $this->filepath;
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
        // run checker for class
        $this->runCheckerForClass($className);
    }

    /**
     * @method DependencyChecker runCheckerForClass
     * Run checker for class
     * @param string $className
     * @throws DependencyFailedForClass
     */
    private function runCheckerForClass(string $className)
    {
       if (count(self::$fileCheckerList['class']) > 0) :

            // extend classes within a file
            $fileClass = self::$fileCheckerList['class'];

            // get trace
            $trace = $this->getSPLAutoloadCall();

            // args should contain the class that was called
            $traceClass = $trace['args'][0];

            // check if both class matches
            if ($traceClass == $className && isset($trace['file'])) :

                // read file and get the filename plus the array of classes
                foreach ($fileClass as $filename => $classes) :

                    // get trace file
                    $traceFile = $trace['file'];

                    // targeting globals also
                    if (strncmp($traceFile, $filename, strlen($filename)) >= 0 || $filename === self::$globalKeyword) :

                        // check $classes get
                        if (isset($classes[$className])) :
                            
                            // developer message
                            $developerMessage = $classes[$className];

                            // replace globals
                            $filename = $filename === self::$globalKeyword ? $traceFile : $filename;

                            // throw exception
                            throw new DependencyFailedForClass($filename, $className, $developerMessage);

                        endif;

                        break;

                    endif;
                    
                endforeach;

                // clean up
                unset($classes, $fileClass, $filename);

            endif;

       endif;
    }

    /**
     * @method DependencyChecker throwableFired
     * @param $exception
     * @throws DependencyFailedForFunction
     * @throws DependencyFailedForVariable
     */
    public function throwableFired(&$exception)
    {
        // run checker for variable
        $this->runCheckerForVariable($exception);

        // run checker for functions
        $this->runCheckerForFunctions($exception);
    }

    /**
     * @method DependencyChecker runCheckerForVariable
     * Run checker for variable
     * @param mixed $exception
     * @throws DependencyFailedForVariable
     */
    private function runCheckerForVariable(&$exception)
    {
        if (count(self::$fileCheckerList['variable']) > 0) :
            
            // get variables
            $variables = self::$fileCheckerList['variable'];

            // get the error message
            $errorMessage = $exception->getMessage();

            // check variables
            foreach ($variables as $file => $variableList) :

                // get file from exception
                $exceptionFile = $exception->getFile();

                // remove leading . 
                $file = preg_replace('/^[.]+/', '', $file);
                
                // check if exception file matches the file in checker list
                if (strrpos($exceptionFile, $file) !== false || $file === self::$globalKeyword) :

                    // check if variable exists in $errorMessage
                    foreach ($variableList as $variable => $message) : 

                        // remove dollar sign from $variable
                        $variable = preg_replace('/[$]+/', '', $variable);

                        // replace globals
                        $file = $file === self::$globalKeyword ? $exceptionFile : $file;

                        // check if variable exists in error message
                        if (strrpos($errorMessage, $variable) !== false) :

                            // throw exception
                            throw new DependencyFailedForVariable($file, $variable, $message, 'variable', $exception);

                        endif;

                    endforeach; 
                    
                    break;

                endif;

            endforeach;

        endif;
    }

    /**
     * @method DependencyChecker runCheckerForFunctions
     * Run checker for functions
     * @param mixed $exception
     * @throws DependencyFailedForFunction
     */
    private function runCheckerForFunctions(&$exception)
    {
        if (count(self::$fileCheckerList['function']) > 0) :
            
            // get functions
            $functions = self::$fileCheckerList['function'];

            // get the error message
            $errorMessage = $exception->getMessage();

            // check functions
            foreach ($functions as $file => $functionList) :

                // get file from exception
                $exceptionFile = $exception->getFile();

                // remove leading . 
                $file = preg_replace('/^[.]+/', '', $file);
                
                // check if exception file matches the file in checker list
                if (strrpos($exceptionFile, $file) !== false || $file === self::$globalKeyword) :

                    // check if variable exists in $errorMessage
                    foreach ($functionList as $function => $message) : 

                        // replace globals
                        $file = $file === self::$globalKeyword ? $exceptionFile : $file;

                        // check if function exists in error message
                        if (strrpos($errorMessage, $function) !== false) :

                            // throw exception
                            throw new DependencyFailedForFunction($file, $function, $message, 'function', $exception);

                        endif;

                    endforeach; 
                    
                    break;

                endif;

            endforeach;

        endif;
    }

    /**
     * @method FileDependencyChecker source
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
     * @method FileDependencyChecker register
     *
     * set source group for source class
     * @param array $sourceGroup
     * @return FrameworkAutoloaderEvents
     */
    public function register(array $sourceGroup) : FrameworkAutoloaderEvents
    {
        return $this;
    }

    /**
     * @method FileDependencyChecker getSPLAutoloadCall
     * @return array
     * Get Standard PHP Library autoload call array
     */
    public function getSPLAutoloadCall() : array
    {
        // read the 
        $exception = new class() extends Exception{

            // @var array autoload call trace
            public $autoloadTrace = [];

            /**
             * @method Exception constructor
             * Checks the stack trace and return array with spl_autoload_call function
             */
            public function __construct()
            {
                // get all traces
                $traces = $this->getTrace();

                // find trace with function spl_autoload_call
                foreach ($traces as $trace) :

                    if (isset($trace['function']) && $trace['function'] == 'spl_autoload_call') :

                        // add trace 
                        $this->autoloadTrace = $trace;

                        // break out of loop
                        break;

                    endif;

                endforeach;

                $trace = $traces = null;
            }
        };

        return $exception->autoloadTrace;
    }
}