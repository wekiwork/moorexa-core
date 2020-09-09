<?php
namespace Lightroom\Security;

use Exception;
use Lightroom\Security\Interfaces\{
    GetterInterface, SetterInterface, SecurityGroupInterface
};
use Closure;
use Lightroom\Exceptions\ClassNotFound;
use Lightroom\Exceptions\InterfaceNotFound;
use ReflectionClass;

/**
 * @package SecurityGroup
 * @author Amadi ifeanyi <amadiify.com>
 */
class SecurityGroup
{
    /**
     * @var SecurityGroupInterface $defaultSecurityGroup
     */
    private static $defaultSecurityGroup = null;

    /**
     * @var SecurityGroup $registeredSecurityGroup
     */
    private static $registeredSecurityGroup = null;

    /**
     * @var SecurityGroup $instance
     */
    private static $instance;

    /**
     * @var array $requiredInterfaces
     */
    private static $requiredInterfaces = [
        SecurityGroupInterface::class,
        GetterInterface::class,
        SetterInterface::class
    ];

    /**
     * @method SecurityGroup constructor
     * Registers a default security group for the framework
     * @param string $className
     * @param Closure $callback
     * @throws ClassNotFound
     */
    public function __construct(string $className, Closure $callback)
    {
        // check if class exists
        if (!class_exists($className)) :
            // security group not found
            throw new ClassNotFound($className);
        endif;

        // set instance
        self::$instance = $this;

        // register security group
        self::$registeredSecurityGroup = [$className, $callback];

        // include security group functions
        include_once __DIR__ . '/../Security/Functions.php';
    }

    /**
     * @method SecurityGroup getDefault
     * This method gets the instance of the default security group. Throws an error if no default group has been registered.
     * @return SecurityGroupInterface
     * @throws Exception
     */
    public static function getDefault() : SecurityGroupInterface
    {
        // get the default security group
        self::loadDefaultGroup();

        // get default security group
        if (self::$defaultSecurityGroup === null) :
            // throw exception
            throw new Exception('Sorry, no security group has been registered. Functions like encrypt(), decrypt() or releated security group functions would not work.');
        endif;

        // return instance
        return self::$defaultSecurityGroup;
    }

    /**
     * @method SecurityGroup loadDefaultGroup
     * This method loads the default security group
     */
    private static function loadDefaultGroup()
    {
        if (self::$defaultSecurityGroup === null) :

            // get classname and callback
            list($className, $callback) = self::$registeredSecurityGroup;

            // create reflection class
            $reflection = new \ReflectionClass($className);

            // check interfaces
            self::$instance->checkRequiredInterfaces($reflection, $className);

            // get instance of security group with a constructor
            $instance = $reflection->newInstance();

            // call closure 
            call_user_func($callback->bindTo($instance, \get_class($instance)));

            // set as default
            self::$defaultSecurityGroup = $instance;

            // clean up
            unset($instance, $reflection, $callback);

        endif;
    }


    /**
     * @method SecurityGroup checkRequiredInterfaces
     * This method checks for the required interfaces
     *
     * @param ReflectionClass $reflection
     * @param string $className
     * @return void
     * @throws InterfaceNotFound
     */
    private function checkRequiredInterfaces(\ReflectionClass $reflection, string $className) : void
    {
        // get interfaces
        $interfaces = array_flip($reflection->getInterfaceNames());

        // run a check of required interfaces
        foreach (self::$requiredInterfaces as &$interface) : 

            // check if interface exists.
            if (!isset($interfaces[$interface])) :

                // interface not found. stop execution
                throw new InterfaceNotFound($className, $interface);
                
            endif;

        endforeach;

        // clean up
        unset($interface, $reflection, $interfaces);
    }
}