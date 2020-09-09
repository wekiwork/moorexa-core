<?php
namespace Lightroom\Adapter\Interfaces;

/**
 * @package GlobalVariables Interface
 * @author Amadi ifeanyi <amadiify.com>
 * 
 * This interface provides a setter and getter method required by the GlobalVariables class for implementing classes
 */
interface GlobalVariablesInterface
{
    /**
     * @method GlobalVariablesInterface read_var
     * @param string $variable_name
     * @return mixed
     * 
     * This returns the value of a variable
     */
    public function read_var(string $variable_name);

    /**
     * @method GlobalVariablesInterface set_var
     * @param string $variable_name
     * @param mixed $variable_value
     * 
     * This sets a variable accessible globally.
     */
    public function set_var(string $variable_name, &$variable_value);
}