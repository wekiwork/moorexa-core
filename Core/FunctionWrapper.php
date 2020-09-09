<?php
namespace Lightroom\Core;

use closure;
use Lightroom\Core\Interfaces\FunctionWrapperInterface;
use Lightroom\Adapter\Configuration\Interfaces\FunctionAttachments;
use ReflectionException;

/**
 * @package Function Wrapper
 * @author fregatelab <fregatelab.com>
 */
class FunctionWrapper implements FunctionWrapperInterface
{
    /**
     * @var array $attachToClass
     * contains valid class that implements FunctionAttachments interface
     */
    private $attachToClass = [];

    /**
     * @var array $createdFunction
     * contains list of created functions
     */
    private $createdFunction = [];

    /**
     * @method FunctionWrapper __construct
     * constructor for function wrapper
     * @param string $globalFunctionFilePath
     */
    public function __construct(string $globalFunctionFilePath = '')
    {
        // load global functions
        $this->loadGlobalFunction($globalFunctionFilePath);
    }

    /**
     * @method FunctionWrapper create
     * create a function. take a name and closure function,
     * this function can be attached to a class
     * @param string $functionName
     * @param closure $functionClosure
     * @return FunctionWrapperInterface
     */
    public function create(string $functionName, closure $functionClosure) : FunctionWrapperInterface
    {
        $this->createdFunction[$functionName] = $functionClosure;

        // clean up
        unset($functionName, $functionClosure);

        // return class instance
        return $this;
    }

    /**
     * @method FunctionWrapperInterface attachTo
     * attach a wrapped function to a listening class
     * @param string $className
     * @return FunctionWrapperInterface
     * @throws ReflectionException
     */
    public function attachTo(string $className) : FunctionWrapperInterface
    {
        // check if attachTo class not found
        $hasClass = isset($this->attachToClass[$className]) ? $this->attachToClass[$className] : false;

        if ($hasClass === false) :
        
            // create reflection class
            $reflection = new \ReflectionClass($className);

            // does class implement function attachments interface
            if ($reflection->implementsInterface(FunctionAttachments::class)) :
            
                $this->attachToClass[$className] = true;

                // has class
                $hasClass = true;
            
            endif;
            
        endif;

        if ($hasClass) :
        
            // class static addFunc method from class $className
            call_user_func($className . '::addFunc', $this->createdFunction);
            
        endif;
        

        // free memory
        $this->createdFunction = [];

        // clean up
        unset($hasClass, $reflection, $className);

        // return class instance
        return $this;
    }

    /**
     * @method FunctionWrapper global functions from file
     * @param string $filepath
     */
    public function loadGlobalFunction(string $filepath)
    {
        if (strlen($filepath) > 2 && file_exists($filepath)) :

            // include file
            include_once $filepath;

        endif;
    }

    /**
     * @method FunctionWrapper getParameters
     * @param mixed $closure
     * @param array $bind (reference)
     * @param array $other
     * @return void
     *
     * This method gets the parameters of a function
     * @throws ReflectionException
     */
    public static function getParameters($closure, &$bind = [], array $other = []) : void
    {
        // @var bool $continue
        $continue = false;

        // check if we have a closure 
        if ((is_string($closure) and strpos($closure, '::') === false) || is_callable($closure)) :
            // update continue 
            $continue = true;
        endif;

        if ($continue) :
        
            // get parameters
            $parameters = self::getFunctionArguments($closure, $other);

            $params = null;

            // @var array $newParameters
            $newParameters = [];

            foreach ($parameters as $index => $parameter) :
            
                // update 
                $newParameters[$index] = null;

                if (isset($parameter[1])) :
                
                    if (is_object($parameter[1])) :
                    
                        // update newParameters
                        $newParameters[$index] = $parameter[1];

                        // remove index
                        unset($parameters[$index]);

                    endif;

                endif;

            endforeach;

            // @var array $values
            $values = array_values($parameters);

            // @var int $localIndex
            $localIndex = 0;

            // update new parameters
            foreach($newParameters as $index => $parameter) :
            
                if ($parameter == null) :
                
                    if (isset($values[$localIndex])) :
                    
                        // get value
                        $value = isset($values[$localIndex][1]) ? $values[$localIndex][1] : null;

                        // update new parameters from $other
                        if (isset($other[$localIndex])) $newParameters[$index] = $other[$localIndex]; 

                        // update new parameters from value
                        if (!is_null($value)) $newParameters[$index] = $value;

                    endif;

                    // update local index
                    $localIndex++;

                endif;

            endforeach;

            // update bind
            $bind = $newParameters;

        endif;

    }

    /**
     * @method FunctionWrapper getFunctionArguments
     * @param mixed $closure
     * @param array $arguments
     * @return array
     * @throws ReflectionException
     */
    private static function getFunctionArguments($closure, array $arguments) : array
    {
        // get reflection class for function
        $reflection = new \ReflectionFunction($closure);

        // get parameters
        $parameters = $reflection->getParameters();

        // @var array $newArray
        $newParameters = [];

        // get parameters
        if (count($parameters) > 0) :
        
            foreach ($parameters as $index => $parameter) :
        
                // update new parameters
                $newParameters[$index][] = $parameter->name;

                // get parameter at index
                $reflectionParameter = new \ReflectionParameter($closure, $index);

                // get reflection class
                $class = $reflectionParameter->getClass();

                if ($class !== null) :
                
                    if ($class->isInstantiable()) :
                    
                        // create instance and update parameters
                        $newParameters[$index][] = new $class->name;

                    endif;
                
                else:
                
                    if ($reflectionParameter->isDefaultValueAvailable()) :
                    
                        // update parameters with default value
                        $newParameters[$index][] = $reflectionParameter->getDefaultValue();

                    endif;

                endif;
            
            endforeach;

        endif;

        // return array
        return $newParameters;
    }
}