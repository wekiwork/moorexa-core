<?php
namespace Lightroom\Packager\Moorexa;
/**
 * @package RouterMethods
 * @author Amadi Ifeanyi <amadiify.com>
 */
class RouterMethods
{
    /**
     * @var array $methods
     * 
     * This array would contain all http request methods pushed including the method handler loaded as a 
     * closure function.
     * 
     * After read, they would be deleted to increase speed and system memory
     */
    private $methods = [];

    /**
     * @method RouterMethods __call
     * @param string $httpMethod
     * @param array $arguments
     * @return RouterMethods
     */
    public function __call(string $httpMethod, array $arguments) : RouterMethods
    {
        // push 
        $this->methods[$httpMethod][] = $arguments;

        // return this
        return $this;
    }

    /**
     * @method RouterMethods reset
     * @param void
     * @return void
     */
    public function resetAll() : void 
    {
        $this->methods = []; // that's it
    }

    /**
     * @method RouterMethods hasMethods
     * @return bool
     */
    public function hasMethods() : bool 
    {
        return (count($this->methods) > 0 ? true : false);
    }

    /**
     * @method RouterMethods getMethods
     * @return array
     */
    public function getMethods() : array 
    {
        return $this->methods;
    }
}