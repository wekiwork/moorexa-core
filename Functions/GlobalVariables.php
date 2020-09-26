<?php
namespace Lightroom\Functions\GlobalVariables;

use Lightroom\Adapter\GlobalVariables;

/**
 * @method GlobalVariables var_set
 * set a variable and make avaliable globally
 * @param string $variable_name
 * @param mixed $variable_value
 * @return mixed
 */
function var_set(string $variable_name, $variable_value)
{
    // using closure to avoid error like
    // accessing method from null
    return GlobalVariables::fromInstance(function() use (&$variable_name, &$variable_value)
    {
        return $this->set_var($variable_name, $variable_value);
    });
}


/**
 * @method GlobalVariables var_get
 * get a variable value pushed to the global namespace
 * @param string $variable_name
 * @param mixed $defaultValue
 * @return mixed
 */
function var_get(string $variable_name, $defaultValue = '')
{
    // using closure to avoid error like
    // accessing method from null
    return GlobalVariables::fromInstance(function() use (&$variable_name, $defaultValue)
    {
        return $this->read_var($variable_name, $defaultValue);
    });
}