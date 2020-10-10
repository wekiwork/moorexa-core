<?php
namespace Lightroom\Core;

use closure;
use Lightroom\Exceptions\PackageManagerException;
use Lightroom\Exceptions\ThrowableExceptions;
use Lightroom\Adapter\Errors\SilentErrorListener;
use Lightroom\Core\Interfaces\{
    PayloadRunnerInterface, SystemPathsInterface
};
use Lightroom\Common\Interfaces\PackageManagerInterface;
use ReflectionException;

/**
 * @package BootCoreEngine
 * @author fregatelab <fregatelab.com> 
 * @author Amadi ifeanyi <amadiify.com>
 */
class BootCoreEngine
{
    /**
     *@var BootCoreEngine characterEncoding
     */
    private $characterEncoding = 'utf-8';

    /**
     * @var bool $displayErrors
     */
    private $displayErrors = false;

    /**
     * @var PackageManagerInterface $packageManager
     */
    private static $packageManager;

    /**
     * @method BootCoreEngine setEncoding
     * Set default encoding for application
     * @param string $encoding
     */
    public function setEncoding(string $encoding)
    {
        // set internal encoding
        ini_set('default_charset', $encoding);

        // make available throughout this class
        $this->characterEncoding = $encoding;

        // clean up
        unset($encoding);
    }

    /**
     * @method BootCoreEngine setTimeZone
     * @param string $timeZone
     * @return void 
     */
    public function setTimeZone(string $timeZone) : void 
    {
        // set the default time zine
        date_default_timezone_set($timeZone);
    }

    /**
     * @method BootCoreEngine setContentType
     * set the default content type
     * @param string $contentType
     */
    public function setContentType(string $contentType)
    {
        // read from request
        if (function_exists('getallheaders')) :

            // check if content type exists
            $headers = getallheaders();

            // check for content type
            $contentType = (isset($headers['Set-Content-Type'])) ? $headers['Set-Content-Type'] : (isset($headers['set-content-type']) ? $headers['set-content-type'] : $contentType);

            // clean 
            $headers = null;

        endif;

        // set application content type
        header('Content-Type: '. $contentType . '; charset='. (string) $this->characterEncoding);

        // clean up
        unset($contentType);
    }

    /**
     * @method BootCoreEngine bootProgram
     * start boot process
     * @param Payload $payload
     * @param PayloadRunnerInterface $runner
     * @throws ThrowableExceptions
     * @throws ReflectionException
     */
    public function bootProgram(Payload $payload, PayloadRunnerInterface $runner)
    {
        try
        {
            // load system processes
            $payload->loadProcesses($runner);
        }
        catch (\Throwable $exception)
        {
            //run throwable exception
            if ($this->displayErrors) throw new ThrowableExceptions($exception);
            
            // call silent error channel
            SilentErrorListener::callChannelWith($exception);
        }
    }

    /**
     * @method BootCoreEngine register system paths
     *
     * This establishes our system paths. You can replace the default system path with yours totally!
     * @param SystemPathsInterface $pathEngine
     * @param closure $pathEngineWrapper
     */
    public function registerSystemPaths(SystemPathsInterface $pathEngine, closure $pathEngineWrapper)
    {
        // call closure
        call_user_func($pathEngineWrapper->bindTo($pathEngine, \get_class($pathEngine)));

        // load system path
        $pathEngine->loadPath();

        // clean up
        unset($pathEngine, $pathEngineWrapper);
    }

    /**
     * @method BootCoreEngine load default package manager
     *
     * A package manager contains payloads in processes, this is the base of the framework
     * You can create a your custom package manager and replace the default package manager.
     * @param Payload $payload
     * @param string $manager
     * @throws ReflectionException
     * @throws PackageManagerException
     */
    public function defaultPackageManager(Payload &$payload, string $manager)
    {
        // packager runtime error
        $runtimeError = 'Class ' . $manager . ' does not exists. Could not run package manager';

        // manager must implement PackageManagerInterface
        if (class_exists($manager)) :

            // create reflection class
            $reflection = new \ReflectionClass($manager);

            // PackageManagerInterface not found
            $runtimeError = 'Class ' . $manager . ' does not implements '.PackageManagerInterface::class.' interface.';
            
            // check if manager implements PackageManagerInterface
            if ($reflection->implementsInterface(PackageManagerInterface::class)) :
            
                $runtimeError = '';

                // create instance without constructor
                $instance = $reflection->newInstanceWithoutConstructor();

                // register package manager
                self::$packageManager = $instance;

                // register payload
                $instance->registerPayload($payload, $this);

            endif;
            
        endif;

        // clean up
        unset($manager, $reflection, $instance, $payload);
        
        // Show runtime error if set
        if ($runtimeError != '') :

            // throw PackageManagerException exception of packager not found or interface implementation failed
            throw new PackageManagerException($runtimeError);

        endif;
    }

    /**
     * @method BootCoreEngine displayErrors
     * @param bool $display
     */
    public function displayErrors(bool $display = false)
    {
        // default switch
        $switch = 'Off';

        if ($display) :
            
            // turn switch on
            $switch = 'On';

        endif;

        // set display error
        $this->displayErrors = $display;

        // switch internal display errors On/Off
        ini_set('display_errors', $switch);
    }

    /**
     * @method BootCoreEngine registerAliases
     * Registers Aliases with file path
     * @param array $aliases
     */
    public static function registerAliases(array $aliases)
    {
        // create an anonymous
        $aliasesObject = is_object(self::$packageManager) && 
                         method_exists(self::$packageManager, 'registerAliases') ? self::$packageManager->registerAliases($aliases) : null;
                         
        // load aliases object
        if (is_object($aliasesObject)) :

            // push to private autoloader
            FrameworkAutoloader::registerPrivateAutoloader($aliasesObject);

            // clean up
            unset($aliasesObject);

        endif;
    }
}