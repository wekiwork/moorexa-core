<?php
namespace Lightroom\Adapter\Configuration;

use Lightroom\Adapter\Configuration\{
    Interfaces\ConfigurationSocketHandlerInterface as SocketInterface, 
    Interfaces\SetSocketArrayInterface
};
use ReflectionException;

/**
 * @package ConfigurationSocket
 * 
 */
trait ConfigurationSocket
{
    use SetSocketArray;

    // @var array $socketArray
    private $socketArray = [];

    /**
     * @method ConfigurationSocket configurationSocket
     * Apply a configuration socket for environment methods
     * @param array $socketArray
     */
    public function configurationSocket(array $socketArray)
    {
        // load socket array
        array_walk($socketArray, $this->configurationSocketClosure());
    }

    /**
     * @method ConfigurationSocket getSocketArray
     */
    public function getSocketArray() : array
    {
        return $this->socketArray;
    }

    /**
     * @method ConfigurationSocket getSocketArray
     * @param string $method
     * @param SetSocketArrayInterface $socketArray
     */
    public function setSocketArray(string $method, SetSocketArrayInterface $socketArray)
    {
        $this->socketArray[$method] = $socketArray;

        // clean up
        unset($socketArray, $method);
    }

    /**
     * @method ConfigurationSocket configurationSocketClosure
     */
    private function configurationSocketClosure() : \closure
    {
        // configuration socket closure
        return function(SocketInterface $handler, string $method)
        {
            // get class
            $className = $handler->getClass();

            // check if class exits then make available for callMethodFromConfigurationSocket method
            if (class_exists($className)) :
            
                // save method and class information in socket array
                $this->setSocketArray($method, $this->setSocketClass($className)->setSocketMethod($handler->getMethod()));

            endif;

            // clean up
            unset($className, $handler, $method);
        };
    }

    /**
     * @method ConfigurationSocket callMethodFromConfigurationSocket
     * call method from application socket
     * @param string $runtimeMethod
     * @param array $arguments
     * @return ConfigurationSocket
     * @throws ReflectionException
     */
    public function callMethodFromConfigurationSocket(string $runtimeMethod, array $arguments)
    {
        // get socket
        $socketArray = $this->getSocketArray();

        // check if method exists in socket array
        if (isset($socketArray[$runtimeMethod])) :
        
            $socketHandler = $socketArray[$runtimeMethod];

            // get socket class
            $socketClass = $socketHandler->getSocketClass();

            // get socket method
            $socketMethod = $socketHandler->getSocketMethod();

            // create reflection 
            $reflection = new \ReflectionClass($socketClass);

            // check for method
            if ($reflection->hasMethod($socketMethod)) :
            
                // get method
                $getMethod = $reflection->getMethod($socketMethod);

                // get instance
                $instance = $reflection->newInstanceWithoutConstructor();

                // load method
                $getMethod->invokeArgs($instance, $arguments);

            endif;

            // clean up
            unset($socketArray, $runtimeMethod, $socketHandler, $socketClass, $socketMethod, $reflection, $instance, $getMethod);

        endif;

        // return class instance
        return $this;
    }

    /**
     * @method ConfigurationSocket magic method for configuration socket
     * @param string $method
     * @param array $arguments
     * @return ConfigurationSocket
     * @throws ReflectionException
     */
    public function __call(string $method, array $arguments)
    {
        // return from configuration socket
        return $this->callMethodFromConfigurationSocket($method, $arguments);
    }
}