<?php
namespace Lightroom\Packager\Moorexa;

use Lightroom\Core\Interfaces\PrivateAutoloaderInterface;

/**
 * @package AliaseAutoloader for Moorexa
 * @author Amadi ifeanyi <amadiify.com>
 * 
 * This maps a namespace to a path for quick namespaces.
 * example.
 * 
 * -Lab
 *  - Account
 *    - Test
 *     - TestUser.php
 * 
 * Can be registered by setting the absolute path in aliases.php
 * Let's take an example;
 * 'Account\TestUser' => 'lab/Account/Test/TestUser.php'
 * we can then call (new Account\TestUser()) to get the instance of that class. It's as easy as that.
 * 
 */
trait AliaseAutoloader
{
    // alias array
    private static $aliases = [];

    // constructor
    public function __construct(array $aliases)
    {
        // push to aliases
        self::$aliases = array_merge(self::$aliases, $aliases);
    }

    /**
     * @method PrivateAutoloaderInterface autoloaderRequested
     * Trigger this when private autoloader is requested
     * @param string $class
     * @return bool
     */
    public function autoloaderRequested(string $class) : bool
    {
        // autoload passed
        $autoloaderPassed = false;
        
        // check if class has been registered
        if (isset(self::$aliases[$class])) :

            // get aliases configuration
            // it should have path as a key
            $config = self::$aliases[$class];

            // get path
            $path = $config;

            // check for path
            if (isset($config['path'])) : 

                // get the path
                $path = $config['path'];

            endif;

            // check if path exists
            if (file_exists($path)) :

                // include path
                include_once $path;

                // update autoload passed
                if (class_exists($class)) $autoloaderPassed = true;

            endif;

            // clean up
            unset($config, $path, $class);

        endif;
       

        // return bool
        return $autoloaderPassed;
    }
}