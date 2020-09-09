<?php
namespace Lightroom\Adapter;

use closure;
use Lightroom\Adapter\Interfaces\GlobalVariablesInterface;

class GlobalVariables
{
    /**
     * @var bool $globalVariablesRegistered
     */
    private static $globalVariablesRegistered = false;

    /**
     * @var GlobalVariablesInterface $registeredVariableClass
     */
    private static $registeredVariableClass = null;

    /**
     * @var array $loadedVariables;
     */
    private static $loadedVariables = [];

    /**
     * @method GlobalVariables constructor
     * @param GlobalVariablesInterface $instance
     * @return void
     * 
     * Registers a class that implements GlobalVariables Interface.
     * It can only be done once.
     */
    public function __construct(GlobalVariablesInterface $instance)
    {
        if (self::$globalVariablesRegistered === false) :

            // register class
            self::$registeredVariableClass = $instance;

            // include Global variable function
            require_once __DIR__ . '/../Functions/GlobalVariables.php';

        endif;
    }

    /**
     * @method GlobalVariables fromInstance
     * @param closure $closure $closure
     *
     * This method takes a closure and bind that closure to the registeredVariableClass instance,
     * only if registeredVariableClass has been registered.
     * @return mixed
     */
    public static function fromInstance(closure $closure)
    {   
        // get return data
        $returnData = null;
        
        if (self::$registeredVariableClass !== null) : 

            // bind closure to registered variable class
            $closure = $closure->bindTo(self::$registeredVariableClass, \get_class(self::$registeredVariableClass));

            // call closure 
            $returnData = $closure();
            
        endif;

        // return mixed data
        return $returnData;
    }

    /**
     * @method GlobalVariables var_set
     * @param string $variable_name
     * @param mixed $variable_value
     *
     * @return mixed $variable_value
     */
    public static function var_set(string $variable_name, $variable_value)
    {
        // push to loaded variables
        self::$loadedVariables[$variable_name] = $variable_value;

        //return value
        return $variable_value;
    }

    /**
     * @method GlobalVariables var_get
     * @param string $variable_name
     * @param mixed $fallback_variable_value
     * 
     * @return mixed $variable_value
     */
    public static function var_get(string $variable_name, $fallback_variable_value = null)
    {
        // check if variable has been loaded previously
        if (isset(self::$loadedVariables[$variable_name])):

          // replace fallback variable value
          $fallback_variable_value = self::$loadedVariables[$variable_name];

        endif;
        
        //return value
        return $fallback_variable_value;
    }

    /**
     * @method GlobalVariables var_drop
     * @param string $variable_name
     * 
     * @return mixed $variable_value
     */
    public static function var_drop(string $variable_name) : void
    {
        // check if variable has been loaded previously
        if (isset(self::$loadedVariables[$variable_name])):

          // drop now
          unset(self::$loadedVariables[$variable_name]);

        endif;
    }
}