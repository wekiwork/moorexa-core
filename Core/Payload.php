<?php
namespace Lightroom\Core;

use Lightroom\Core\{
    PayloadAccessMethods, Interfaces\PayloadInterface, 
    Interfaces\PayloadRunnerInterface
};
use Lightroom\Router\Middlewares;
use Lightroom\Core\FrameworkAutoloader;
/**
 * @package Payload utility program
 * 
 * This program registers classes needed for our application booting process,
 * it's informed of which process to call next. 
 * It would only call a process from it source if not called previously
 */
class Payload
{
    /**
     * @var array $payloads
     */
    private $payloads = [];

    /**
     * @var string $process
     * active process name
     */
    private $process = '';

    /**
     * @var bool $payloadLocked
     */
    private static $payloadLocked = false;

    /**
     * @var bool $middlewareNamespaceRegistered
     */
    private static $middlewareNamespaceRegistered = false;

    /**
     * @method Payload register
     * @param string $processName
     * @param PayloadInterface $handler
     * 
     * This method registers a process name for lazy loading
     * @return Payload
     */
    public function register(string $processName, PayloadInterface $handler) : Payload
    {
        // set process name
        $this->process = $processName;

        // get class and method
        $this->registerPayload($handler->getClass(), $handler->getMethod(), $handler->getArguments());

        // return instance
        return $this;
    }

    /**
     * @method Payload remove
     * @param string $processName
     * @param PayloadInterface $handler
     * 
     * This method removes a process name from payloads
     * @return Payload
     */
    public function remove(string $processName) : Payload
    {
        // remove payload
        if (isset($this->payloads[$processName])) unset($this->payloads[$processName]);

        // return instance
        return $this;
    }

    /**
     * @method Payload handler
     * @param string $className
     * @param string $method
     * 
     * This method registers the classname and method
     * @return PayloadAccessMethods
     */
    public function handler(string $className, string $method = '') : PayloadAccessMethods
    {
        // create instance
        $accessor = new PayloadAccessMethods();
        
        // set class name
        $accessor->setClass($className);

        // set class method
        $accessor->setMethod($method);

        // return instance
        return $accessor;
    }

    /**
     * @method Payload next
     * @param string $processName
     * @return void
     * 
     * This method registers the next process to call
     */
    public function next(string $processName) : void
    {
        $this->payloads[$this->process]['next'] = $processName;
    }

    /**
     * @method Payload loadProcesses
     * @param PayloadRunnerInterface $runner
     * 
     * This method load processes when ready
     * @return array
     */
    public function loadProcesses(PayloadRunnerInterface $runner) : array
    {
        // all process called
        $processCalled = [];
        $processIndex = 0;

        // use runner
        $runner->setPayloads($this->payloads);
        $runner->callLoadProcessWhenComplete($processIndex, $processCalled);

        // clean up
        unset($runner, $processIndex);

        return $processCalled;
    }

    /**
     * @method Payload clearPayloads
     * @return Payload
     * 
     * This method clears the payloads
     */
    public function clearPayloads() : Payload 
    {
        $this->payloads = [];
        $this->process = '';

        return $this;
    }

    /**
     * @method Payload payloadCanBeRegistered
     * @param string $processName
     * @return bool
     */
    public static function payloadCanBeRegistered(string $processName) : bool 
    {
        // keep json data
        static $middlewareJsonData;

        // @var bool $register
        $register = self::$payloadLocked === false ? true : false;

        // payload not locked
        if ($register === true) :

            // check for middlewares
            $middlewareJson = SOURCE_BASE_PATH . '/middlewares.json';

            // check if it exists
            if (file_exists($middlewareJson)) :

                if (is_null($middlewareJsonData)) :

                    // load json
                    $middlewareJson = json_decode(trim(file_get_contents($middlewareJson)));

                    // cache
                    $middlewareJsonData = $middlewareJson;

                endif;

                // do we have payloads
                if (isset($middlewareJsonData->payloads)) :

                    // register namespace
                    if (self::$middlewareNamespaceRegistered === false) :

                        // @var string $directory
                        $directory = SOURCE_BASE_PATH . '/utility/Middlewares';

                        if (is_dir($directory)) :

                            // register namespace
                            FrameworkAutoloader::registerNamespace([
                                // register namespace for middleware
                                'Moorexa\Middlewares\\' => $directory,
                            ]);

                        endif;

                        // done
                        self::$middlewareNamespaceRegistered = true;

                    endif;

                    // do we have this process name
                    foreach ($middlewareJsonData->payloads as &$processObject) :

                        // check for process name
                        if (isset($processObject->{$processName})) :

                            // get the middleware
                            $middleware = $processObject->{$processName};

                            // can we load middleware
                            if (strlen($middleware) > 2) :

                                // load others
                                $middlewareArray = explode(',', $middleware);

                                // now we can continue
                                foreach ($middlewareArray as $middleware) :

                                    // trim off any white space
                                    $middleware = trim($middleware);

                                    // continue if middleware exists
                                    if (!class_exists($middleware)) :

                                        // show error
                                        echo "Could not load middleware #{$middleware}, class wasn't found!";

                                        // kill app
                                        die;

                                    endif;

                                    // load Middleware
                                    Middlewares::loadMiddleware($middleware, [$processName]);

                                    // middleware loaded
                                    if (!Middlewares::callLoadedMiddleware([$processName])) :

                                        // lock payload
                                        self::$payloadLocked = true;

                                        // break out
                                        return false;

                                    endif;


                                endforeach;

                            endif;

                        endif;

                    endforeach;

                endif;

                // clean up
                $middlewareJson = null;
                $processObject = null;

            endif;

        endif;


        // register process
        return $register;
    }

    /**
     * @method Payload registerPayload
     * @param string $className
     * @param string $classMethod
     * @param array $arguments
     * @return void
     * 
     * registers a process name plus class for lazy loading
     */
    private function registerPayload(string $className, string $classMethod, array $arguments) : void
    {
        $this->payloads[$this->process] = ['class' => $className, 'method' => $classMethod, 'arguments' => $arguments];
    }
}