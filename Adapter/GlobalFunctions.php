<?php
namespace Lightroom\Adapter;

use Lightroom\Core\FunctionWrapper;
use Lightroom\Adapter\Configuration\{
    Interfaces\FunctionAttachments, FunctionAttachment
};
/**
 * @package global functions class
 * @author Amadi Ifeanyi <amadiify.com>
 */
class GlobalFunctions extends FunctionWrapper implements FunctionAttachments
{
    use FunctionAttachment;

    /**
     * @var GlobalFunctions instance
     */
    public static $instance;

    /**
     * @method FunctionAttachments add function
     * @param array $functionArray
     * @return void
     */
    public static function addFunc(array $functionArray)
    {
        self::$addedFunctions[self::getClass()][] = $functionArray;

        if (is_null(self::$instance)) :
        
            self::$instance = new self;

        endif;
    }

    /**
     * @method GlobalFunctions magic method
     * @param string $functionName
     * @param array $functionArgs
     * @return mixed
     */
    public function __call(string $functionName, array $functionArgs)
    {
        if (self::hasFunc($functionName))
        
            // call closure
            return call_user_func_array(self::getFunc($functionName), $functionArgs);
        

        // create on runtime
        $this->createRuntimeFunction($functionName, $functionArgs);
    }

    /**
     * @method GlobalFunctions create runtime function for
     * @param string $functionName
     * @param array $functionArgs
     */
    private function createRuntimeFunction(string $functionName, array $functionArgs)
    {
        $hasClosure = isset($functionArgs[0]) && is_callable($functionArgs[0]) ? true : false;

        // check if closure exists
        if ($hasClosure) :
        
            $closure = $functionArgs[0];

            // get class
            $className = get_class($closure);

            if ($className == 'Closure') :
            
                // add function
                self::addFunc([$functionName => $closure]);
            
            endif;
        
        endif;
    }
}