<?php
namespace Lightroom\Packager\Moorexa\Configuration;

use closure;
use Lightroom\Exceptions\ClassNotFound;
use Lightroom\Vendor\{
    Monolog\MonologWrapper, 
    Whoops\WhoopsWrapper
};
use Lightroom\Adapter\{
    Configuration\Environment, FileDependencyChecker, 
    GlobalVariables as GlobalVars, ClassManager
};
use Lightroom\Packager\Moorexa\{
    MoorexaGlobalVariables, BootloaderConfiguration, 
    MoorexaSilentErrorListener
};
use Lightroom\Core\Setters\Constants;
use Lightroom\Security\SecurityGroup;
use Lightroom\Packager\Moorexa\Helpers\RouterControls;
/**
 * @package Moorexa Packager framework configuration
 * @author amadi ifeanyi <amadiify.com>
 */
trait DefaultPackagerConfiguration
{
    /**
     * @method DefaultPackagerConfiguration defaultPackagerHandlers
     * @return closure
     * 
     * This registers a default logger and exception handler.
     * We would be using monolog for logging and Whoops for exception handling.
     */
    public function defaultPackagerHandlers() : closure
    {
        return function()
        {
            // register error loggers
            $this->loggerHandlers([
                // Sends your logs to files, sockets, mail inbox, databases and various web services
                'monolog' => ClassManager::ifAvailable(\Monolog\Logger::class, MonologWrapper::class)
            ], 

            $this->default('monolog'));

            // listen for global warnings and more
            $this->globalWarnings([
                'function' => [
                    'var_set(' => 'for setting a variable globally. You must register a global variable manager to access this functionality.',
                    'var_get(' => 'for getting a variable from global namespace. You must register a global variable manager to access this functionality.',
                    'func(' => 'a wrapper for functions created and assigned to a class. FunctionLibrary must be imported before func() can be used.',
                    'Security/Functions.php' => 'for loading some helper functions like encrypt(), decrypt(), hash(), verify() into our application.'
                ],
                'variable' => [
                    '\$set' => 'for setting global constants. It\'s exported from the default package configuration file.'
                ]
            ]);

            // exception handler
            $this->exceptionHandler($this->exceptionClass(WhoopsWrapper::class)->exceptionMethod('PrettyPageHandler'));

            // register a silent listener
            $this->silentListener(MoorexaSilentErrorListener::class);

        };
    }

    /**
     * @method DefaultPackagerConfiguration defaultPackagerPaths
     * @return closure
     * 
     * This registers all the paths we need for our application,
     * At the end, we can access those part via func()->const('<name>') or PATH_TO_<name>
     */
    public function defaultPackagerPaths()
    {
        return function()
        {
            // register base file
            $this->setBaseFile(DISTRIBUTION_BASE_PATH . '/config/paths.php');
        };
    }

    /**
     * @method DefaultPackagerConfiguration defaultPackagerDependencies
     * @return closure
     * 
     * This provides a clearer error message when dependency couldn't be found. 
     * You can register classes, trait, interfaces required to a class. 
     * Dependency checker will require Lightroom\Core\FrameworkAutoloader running for it to work.
     */
    public function defaultPackagerDependencies() : closure
    {
        return function(){

            // for moorexa bootloader configuration
            $this->source(BootloaderConfiguration::class)->register([
                \Lightroom\Packager\Moorexa\BootloaderPrivates::class => 'for private methods on BootloaderConfiguration. it contains files like readEnvironmentVariables, which is important for reading environment variables.',
                \Symfony\Component\Yaml\Yaml::class => 'for reading YAML configuration environment file. To install, open up your terminal or cmd, cd to '.getcwd().' and run "php assist install"',
                \Lightroom\Adapter\Configuration\Interfaces\EnvironmentInterface::class => 'for setting and reading environment variables.',
                \Lightroom\Packager\Moorexa\DirectoryAutoloader::class => 'for autoloading classes withing a directory.',
                \Lightroom\Core\FrameworkAutoloader::class => 'for registering a private autoloader and namespace to the system from finder in config.php and aliases.php',
                \Lightroom\Packager\Moorexa\AutoloaderCachingSystem::class => 'for caching autoloaded files and classes to improve performance throughout our application.',
                \Lightroom\Common\JSONHelper::class => 'for reading and saving paths in a json format to a file. AutoloaderCachingSystem and DirectoryAutoloader requires this trait.'
            ]);

            // for moorexa global variables
            $this->source(MoorexaGlobalVariables::class)->register([

            ]);


            // for security group
            $this->source(SecurityGroup::class)->register([
                \Lightroom\Security\Interfaces\GetterInterface::class => 'for getting custom security group configuration.',
                \Lightroom\Security\Interfaces\SetterInterface::class => 'for setting custom security group configuration.',
            ]);
            
            // for QueueHandler
            $this->source(QueueHandler::class)->register([
                \PhpAmqpLib\Message\AMQPMessage::class => 'for rabbitmq jobs. Please run "composer require php-amqplib/php-amqplib"',
                \PhpAmqpLib\Connection\AMQPStreamConnection::class => 'for rabbitmq jobs. Please run "composer require php-amqplib/php-amqplib"',
            ]);
        };
    }

    /**
     * @method DefaultPackagerConfiguration defaultPackagerConfiguration
     * @return closure
     *
     * This helps import files important to our environment.
     * This in turn allow the use of func(), error(), logger(), app(), env() functions throughout our application
     * @throws ClassNotFound
     */
    public function defaultPackagerConfiguration() : closure
    {
        // using file dependency checker
        $file = ClassManager::singleton(FileDependencyChecker::class);

        // get stack files
        $stackFiles = array(
           
           // import function library globally
           $file->path(FRAMEWORK_BASE_PATH . '/Functions/FunctionLibrary.php')->dependency([
               'class' => [
                 \Lightroom\Core\FunctionWrapper::class => 'for creating function that can be attached to a class or made globally.',
               ],
           ]),
   
           // import config file globally
           // This file has the $socket variable, so we can register configuration sockets.
           PATH_TO_CONFIG . '/config.php',
           
           // import class aliases, and push to FrameworkAutoloader
           $file->path(PATH_TO_CONFIG . '/aliases.php')->dependency([
                'variable' => [
                    '$socket' => 'for adding socket class and method to configurationSocket. Must be an instance of ConfigurationSocketHandler class'
                ],
            ]),
        );

        // clean up
        unset($file);

        // Closure of FrameworkConfiguration
        return function() use ($stackFiles)
        {
            $this->loadClass(Environment::class, $this->import($stackFiles)->varName('config')->export([
                /**
                 * @var string $globalfunc
                 * this exported variable would be used in FunctionLibrary,
                 * to request for the global functions with the functionWrapper class
                 */
                'globalfunc' => FRAMEWORK_BASE_PATH . '/Functions/GlobalFunctions.php']
            ));

             $this->loadClass(Constants::class, $this->import([ PATH_TO_CONFIG . '/constants'])->export([
                 /**
                  * @var string $set
                  * This exported variable would be used in constants.php to make room for global constants
                  */
                 'set' => GlobalVars::var_get('global-constant')
             ]));
        };
    }

    /**
     * @method defaultPackagerSecurityGroup
     * @return closure
     */
    public function defaultPackagerSecurityGroup() : closure
    {
        return function(){
            
            // set security group certificate
            $this->setEncryptionCertificate(FRAMEWORK_BASE_PATH . '/Security/Certificates/certificate.key');

            // set encryption salt
            $this->setEncryptionSalt(FRAMEWORK_BASE_PATH . '/Security/Salts/128bitSaltedString.key');
        };
    }

    /**
     * @method RouterHandler useDevelopmentServer
     * @return void
     * @throws RequestManagerException
     */
    public static function useDevelopmentServer() : void
    {
        $server = new class()
        {
            use RouterControls;
            
            public function __construct()
            {
                // @var string $software
                $software = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'No server software';

                // @var string $softwareString
                $softwareString = preg_quote("PHP ".phpversion()." Development Server");

                // check if php development server was used.
                if (preg_match("/($softwareString)/i", $software)) :
                
                    // set the server type
                    $_SERVER['SERVER_TYPE'] = 'moorexa_php_server';

                    // the request uri
                    $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
                    
                    // get request uri
                    $requestUri = ltrim($requestUri, '/');

                    // get the beautiful url target
                    $target = self::loadConfig()['beautiful_url_target'];

                    // check if $requestUri is not empty
                    if ($requestUri != '' && !isset($_GET[$target])) :

                        // decode url
                        $parsedUrl = parse_url(rawurldecode($requestUri));

                        // update $_GET
                        $_GET[$target] = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';

                    endif;

                endif;
            }
        };

        // clean up the memory
        $server = null;
        
    }
}
