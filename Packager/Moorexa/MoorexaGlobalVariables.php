<?php
namespace Lightroom\Packager\Moorexa;

use Opis\Closure\SerializableClosure;
use Lightroom\Adapter\Interfaces\GlobalVariablesInterface;

/**
 * @package Moorexa Global Variables handler
 * @author Amadi Ifeanyi <amadiify.com>
 */
class MoorexaGlobalVariables implements GlobalVariablesInterface
{
    // public variables
    private static $globalVars = [];

    /**
     * @method MoorexaGlobalVariables read_var
     * @param string $variable_name
     * @return mixed
     *
     * This returns the value of a variable
     */
    public function read_var(string $variable_name)
    {
        /**
         * @var mixed $value
         */
        $variable_value = null;

        // check if variable has been set
        if (isset(self::$globalVars[$variable_name])) :

            // get value
            $variable_value = self::$globalVars[$variable_name];

        endif;

        // return mixed value
        return $variable_value;
    }

    /**
     * @method MoorexaGlobalVariables set_var
     * @param string $variable_name
     * @param mixed $variable_value
     *
     * This sets a variable accessible globally.
     * @return mixed
     */
    public function set_var(string $variable_name, &$variable_value)
    {
        // push data to global vars
        self::$globalVars[$variable_name] = &$variable_value;

        // return value
        return $variable_value;
    }
}