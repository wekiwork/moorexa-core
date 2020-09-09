<?php

use Lightroom\Adapter\{
    GlobalFunctions, Configuration\Environment, 
    ProgramFaults, Container, ClassManager
};
use Lightroom\Events\EventHelpers;
use Lightroom\Common\Logbook;
use Lightroom\Requests\Filter;
use function Lightroom\Functions\GlobalVariables\{var_set, var_get};


// global func library
function func() { return GlobalFunctions::$instance; }

// global environment getter function
function env( string $name, string $value = '' ) { return Environment::getEnv($name, $value); }

// global environment setter function
function env_set( string $name, $value = '' ) { return Environment::setEnv($name, $value); }

// global error function
function error() { return new class(){ use ProgramFaults; }; }

// load classes from container
function app(...$arguments)
{
    // method to load
    $method = count($arguments) > 0 ? 'load' : 'instance';

    // return container instance
    return call_user_func_array([Container::class, $method], $arguments);
}

// event class helper
function event(string $name = '', $callback = null)
{
    // load event helper
    $eventHelper = EventHelpers::loadAll();

    // return a class for Dispatcher, Listener, and AttachEvent
    if ($name === '') return call_user_func($eventHelper['basic']);

    // load class
    $eventClass = call_user_func($eventHelper['shared'], $name);

    // load callback
    if ($callback !== null && is_callable($callback)) :

        // load callback
        return call_user_func($callback->bindTo($eventClass), $eventClass);

    endif;

    // return event class
    return $eventClass; 
}

// set global variable
function gvar(string $variableName, $variableValue = null)
{
    // get variable value
    if ($variableValue === null) :

        // get the value and remove
        $value = var_get($variableName);

        // remove variable
        Lightroom\Adapter\GlobalVariables::var_drop($variableName);

        // return value
        return $value;

    endif;  

    // set variable 
    var_set($variableName, $variableValue);
}

// load filter handler
function filter(...$arguments) {  return call_user_func_array([Filter::class, 'apply'], $arguments); }

/**
 * @method Logbook logger
 * 
 * create logger switch function
 * this function by default, would return the default logger
 * you can pass a logger name to make a quick switch.
 */
function logger(string $logger = '')
{
    return $logger != '' ? Logbook::loadLogger($logger) : Logbook::loadDefault();
}