<?php
namespace Lightroom\Adapter\Configuration\Interfaces;

/**
 * @package Function Attachments
 * This interface contains all the required methods needed for a class to listen for attached functions
 */
interface FunctionAttachments
{
    /**
     * @method FunctionAttachments get function
     * @param string $functionName
     * @return mixed
     */
    public static function func(string $functionName);

    /**
     * @method FunctionAttachments has function
     * @param string $functionName
     * @return bool
     */
    public static function hasFunc(string $functionName) : bool;

    /**
     * @method FunctionAttachments get function closure
     * @param string $functionName
     * @return mixed
     */
    public static function getFunc(string $functionName);

    /**
     * @method FunctionAttachments add function
     * @param array $functionArray
     * @return void
     */
    public static function addFunc(array $functionArray);
    /**
     * @method FunctionAttachments get all functions
     * @return array 
     */
    public static function allFunc() : array;

    /**
     * @method FunctionAttachments remove function
     * @param string $functionName
     * @return bool
     */
    public static function removeFunc(string $functionName) : bool;
    /**
     * @method FunctionAttachments get calling class
     * @return string 
     */
    public static function getClass() : string;
}