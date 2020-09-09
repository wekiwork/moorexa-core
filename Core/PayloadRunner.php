<?php
namespace Lightroom\Core;

use closure;
use Lightroom\Exceptions\ClassNotFound;
use Lightroom\Exceptions\MethodNotFound;
use Lightroom\Core\Interfaces\{
    PayloadRunnerInterface, PayloadProcess
};
use Lightroom\Exceptions\PayloadException;
use ReflectionException;

/**
 * @package Default Payload Runner
 * @author fregatlab <fregatelab.com>
 * @author Amadi ifeanyi <amadiify.com> 
 * 
 * This class would help execute payload processes
 */
class PayloadRunner implements PayloadRunnerInterface
{

    /**
     * @var array $payloads
     */
    private $payloads = [];

    /**
     * @method setPayloads
     * You should create a property for payloads
     * @param array $payloads
     */
    public function setPayloads(array $payloads)
    {
        $this->payloads = $payloads;
    }

    /**
     * @method Payload callLoadProcessWhenComplete
     * call load process when current process has been complete
     * @param int $processIndex
     * @param array $processCalled
     */
    public function callLoadProcessWhenComplete(int $processIndex, array &$processCalled)
    {
        // closure function for processes
        $loadProcessFrom = function($processIndex) use (&$processCalled)
        {
            $loadProcess = function($processIndex, &$processCalled)
            {   
                // get process name
                $process = array_keys($this->payloads)[$processIndex];

                // run process
                return $this->loadProcess($process, $processCalled);
            };
            
            // process has been completed 
            $processComplete = $loadProcess($processIndex, $processCalled);

            // call next process
            if ($processComplete) :
            
                $next = $processIndex + 1;

                if ($next < count($this->payloads))
        
                    // call the next process
                    $this->callLoadProcessWhenComplete($next, $processCalled);

            endif;

            // clean up
            unset($loadProcess, $processComplete, $next);
                
        };

        if (count($this->payloads) > 0) :
        
            // call process
            $loadProcessFrom($processIndex);
        
        endif;
    }


    /**
     * @method Payload loadProcess
     * load process and register it
     * @param string $process
     * @param array $processCalled
     * @return bool
     * @throws ClassNotFound
     * @throws MethodNotFound
     * @throws ReflectionException
     */
    public function loadProcess(string $process, array &$processCalled) : bool
    {
        // process complete
        $processComplete = false;

        // can we register payload
        if (Payload::payloadCanBeRegistered($process)) :

            if (!isset($processCalled[$process])) :
        
                // move cursor
                $processComplete = false;

                // check if payload target exists
                if (isset($this->payloads[$process])) :
                
                    // get handler
                    $handler = $this->payloads[$process];

                    // get next process
                    $nextProcess = isset($handler['next']) ? $handler['next'] : null;

                    // get instance and reflection class
                    list($instance, $reflection) = $this->getProcessInstance($handler);
                        
                    // closure to move cursor to the next process
                    $processCompleteClosure = $this->getProcessCompleteClosure($processCalled, $process, $nextProcess, $processComplete);

                    // get closure
                    $processCompleteClosure = $processCompleteClosure->bindTo($this, $instance);
                    
                    // load processComplete method from instance
                    if ($reflection->implementsInterface(PayloadProcess::class)) :
                    
                        $instance->processComplete($processCompleteClosure);
                    
                    else:

                        // skip, move cursor forward
                        $processCompleteClosure();

                        if ($processComplete === false) :

                            // get next process
                            if ($nextProcess !== null and !isset($this->payloads[$nextProcess])) :

                                // continue with process
                                $processComplete = true;

                            endif;

                        endif;

                    endif;

                    // clean up
                    unset($processCompleteClosure, $handler, $instance, $nextProcess);

                endif;
                        
            else:
                
                // called previously, so we skip and move cursor to next.
                $processComplete = true;
                
            endif;

        endif;

        // @var bool return process complete
        return $processComplete;
    }

    /**
     * @method PayloadRunner getProcessCompleteClosure
     * wrap the next process to a closure function and return closure
     * @param $processCalled
     * @param $process
     * @param $nextProcess
     * @param $processComplete
     * @return closure
     */
    public function getProcessCompleteClosure(&$processCalled, $process, $nextProcess, &$processComplete) : closure
    {
        return function() use (&$processCalled, $process, $nextProcess, &$processComplete)
        {
            // process complete
            $processComplete = true;

            // register process
            $processCalled[$process] = 'registered #' .time();

            if ($nextProcess !== null) :

                // call next process
                $processComplete = false;

                if ($this->loadProcess($nextProcess, $processCalled))
                    
                    // process complete
                    $processComplete = true;
                
            endif;
        };

        
    }

    /**
     * @method PayloadRunner clearPayloads
     * @return PayloadRunner
     */
    public function clearPayloads() : PayloadRunner
    {
        $this->payloads = [];

        // return instance
        return $this;
    }

    /**
     * @method PayloadRunner getProcessInstance
     * get process class instance
     * @param array $handler
     * @return array
     * @throws ClassNotFound
     * @throws MethodNotFound
     * @throws ReflectionException
     */
    private function getProcessInstance(array $handler) : array
    {

        // check class existence
        if (!class_exists($handler['class'])) :

            // class not found
            throw new ClassNotFound($handler['class']);

        endif;

        // reflection class
        $reflection = new \ReflectionClass($handler['class']);

        // get instance 
        $instance = null;

        // call class
        if ($handler['method'] == '') :
            
            // check if class has a constructor
            if ($reflection->hasMethod('__construct')) :

                // get instance and pass arguments
                $instance = $reflection->newInstanceArgs($handler['arguments']);

            else :

                // get instance without passing arguments
                $instance = $reflection->newInstanceWithoutConstructor();
            
            endif;
        
        else:
        
            // call class without constructor
            $instance = $reflection->newInstanceWithoutConstructor();

            if (!method_exists($instance, $handler['method'])) :

                // class method not found
                throw new MethodNotFound($handler['class'], $handler['method']);
                
            endif;

            // call method
            call_user_func_array([$instance, $handler['method']], $handler['arguments']);
        
        endif;

        return [$instance, $reflection];
    }
}