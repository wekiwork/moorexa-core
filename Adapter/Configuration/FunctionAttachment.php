<?php
namespace Lightroom\Adapter\Configuration;

/**
 * @package  Function Attachment trait
 * Class must implement FunctionAttachments interface
 */
trait FunctionAttachment
{
    /**
     * @var array $addedFunctions
     */
    private static $addedFunctions = [];

    /**
     * @method FunctionAttachment get function
     * @param string $functionName
     * @param mixed $arguments
     * @return mixed 
     */
    public static function func(string $functionName, ...$arguments)
    {
        if (self::hasFunc($functionName)) :

            // start extraction from index 1
            $arguments = array_splice($arguments, 1);

            // call closure
            return call_user_func_array(self::getFunc($functionName), $arguments);

        endif;

        return function (){};
    }

    /**
     * @method FunctionAttachment get function closure
     * @param string $functionName
     * @return mixed
     */
    public static function getFunc(string $functionName)
    {
        return self::allFunc()[$functionName];
    }

    /**
     * @method FunctionAttachment has function
     * @param string $functionName
     * @return bool
     */
    public static function hasFunc(string $functionName) : bool
    {
        // get functions
        $functions = self::allFunc();

        // @var has function
        $hasFunction = false;

        // check here
        if (isset($functions[$functionName])) :
        
            $hasFunction = true;

        endif;

        // clean up
        unset($functions, $functionName);

        // return bool
        return $hasFunction;
    }

    /**
     * @method FunctionAttachment add function
     * @param array $functionArray
     * @return void
     */
    public static function addFunc(array $functionArray)
    {
        self::$addedFunctions[self::getClass()][] = $functionArray;

        // clean up
        unset($functionArray);
    }

    /**
     * @method FunctionAttachment get all functions
     * @return array 
     */
    public static function allFunc() : array
    {
        // @var array function list
        $functionList = (isset(self::$addedFunctions[self::getClass()]) ? self::$addedFunctions[self::getClass()] : []);

        if (count($functionList) > 0) :
         
            // create temp array
            $functionListTempArray = [];

            // using array walk
            foreach($functionList as $_functionList) :

                $functionListTempArray = array_merge($functionListTempArray, $_functionList);
            
            endforeach;

            // all done
            $functionList = $functionListTempArray;

            // clean up
            unset($functionListTempArray, $_functionList);
        
        endif;

        // function list array
        return $functionList;
    }

    /**
     * @method FunctionAttachment remove function
     * @param string $functionName
     * @return bool
     */
    public static function removeFunc(string $functionName) : bool
    {
        // @var array function list
        $functionLists = (isset(self::$addedFunctions[self::getClass()]) ? self::$addedFunctions[self::getClass()] : []);

        // @var bool $removed
        $removed = false;

        if (count($functionLists) > 0) :
        
            // create temp array
            $functionListTempArray = [];

            // using array walk
            foreach ($functionLists as $functionList) :
            
                if (isset($functionList[$functionName])) :
                
                    unset($functionList[$functionName]);

                    $removed = true;
                    
                endif;

                $functionListTempArray = array_merge($functionListTempArray, $functionList);
                
            endforeach;

            // save 
            self::$addedFunctions[self::getClass()] = $functionListTempArray;

            // clean up
            unset($functionListTempArray, $functionList);
        
        endif;

        return $removed;
    }

    /**
     * @method FunctionAttachment get calling class
     * @return string
     */
    public static function getClass() : string
    {
        return get_called_class();
    }
}