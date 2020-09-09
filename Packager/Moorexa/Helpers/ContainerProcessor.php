<?php
namespace Lightroom\Packager\Moorexa\Helpers;

use Lightroom\Common\File;
use Lightroom\Adapter\Container;
use Lightroom\Core\FrameworkAutoloader;
use Lightroom\Adapter\Interfaces\ContainerInterface;
use Lightroom\Core\Interfaces\PrivateAutoloaderInterface;
/**
 * @package Moorexa Container Processor
 * @author Amadi Ifeanyi <amadiify.com>
 */
class ContainerProcessor implements ContainerInterface, PrivateAutoloaderInterface
{
    /**
     * @var array $registry
     */
    private static $registry = [];

    /**
     * @var bool $privateAutoloaderRegistered
     */
    private static $privateAutoloaderRegistered = false;

    /**
     * @var array $classNotFound
     */
    private static $classNotFound = [];

    /**
     * @method ContainerInterface registryCalled
     * @param Container $container
     * @return void
     */
    public static function registryCalled(Container $container) : void
    {
        // get all registry
        $all = $container->all();

        // register this class as a private autoloader
        if (count($all) > 0) :

            // register
            self::$registry = array_change_key_case($all, CASE_UPPER);

            // register now
            if (self::$privateAutoloaderRegistered === false) :

                // register autolader
                FrameworkAutoloader::registerPrivateAutoloader(new ContainerProcessor);

                // update bool
                self::$privateAutoloaderRegistered = true;

            endif;

        endif;
    }

    /**
     * @method ContainerInterface classCalled
     * @param string $className
     * @return void
     */
    public static function classCalled(string $className) : void
    {

    }

    /**
     * @method ContainerInterface classDropped
     * @param string $className
     * @return void
     */
    public static function classDropped(string $className) : void
    {

    }

    /**
     * @method PrivateAutoloaderInterface autoloaderRequested
     * This method would be called by the FrameworkAutoloader during runtime.
     * You can implement your logic in this method and must return a boolean (true | false)
     * @param string $class
     * @return bool
     */
    public function autoloaderRequested(string $class) : bool
    {
        // get class base and convert to upper case 
        $baseClassName = strtoupper(basename(str_replace('\\', '/', $class)));

        // @var bool $classFound
        $classFound = false;

        // @var bool $continue 
        $continue = true;

        // hash class name
        $filename = __DIR__ . '/Containers/' . md5($class) . '.php';

        // interface cache file
        $interfaceCacheFile = __DIR__ . '/Containers/interfaces.json';

        // get json data
        $jsonData = json_decode(file_get_contents($interfaceCacheFile));

        // convert to an array
        $arrayData = is_object($jsonData) ? func()->toArray($jsonData) : [];

        // check if file exists
        if (file_exists($filename)) :

            // update continue
            $continue = false;

            if (is_object($jsonData)) :

                // get class hash
                $classHash = md5($class);

                // check if hash exists
                if (isset($arrayData[$classHash])) :

                    // check if file has been updated
                    $cacheFile = $arrayData[$classHash]['file'];

                    // get timestamp
                    $timestamp = $arrayData[$classHash]['timestamp'];

                    // recache file
                    if (filemtime($cacheFile) != $timestamp) $continue = true;

                endif;

            endif;

            if ($continue === false) :

                // update checker
                $classFound = true;

                // include file
                include_once $filename;

            endif;

        endif;

        // create file
        if ($continue) :

            // document body
            $document = ['<?php'];

            // check if base class exists
            foreach (self::$registry as $baseClass => $originalClass) :

                // get clean baseclass
                $baseClass = basename(str_replace('\\', '/', $baseClass));

                // check for class from base class
                if (strtoupper($baseClass) == $baseClassName) :

                    if (!isset(self::$classFound[$baseClassName])) :

                        // update bool
                        $classFound = true;

                        // document body
                        $document = ['<?php'];

                        // extract namespace
                        $classWorkedOn = str_replace('\\', '/', $class);

                        // @var string $namespace
                        $namespace = '';

                        // check for namespace
                        if (strpos($classWorkedOn, '/') !== false) :

                            // get namespace
                            $namespace = substr($classWorkedOn, 0, strrpos($classWorkedOn, '/'));

                            if (strlen($namespace) > 1) $document[] = "namespace ". str_replace('/', '\\', $namespace) . ';';

                        endif;

                        // @var bool $create
                        $create = false;

                        // add comment
                        $document[] = "/**\n *@package ".basename($classWorkedOn)." Container\n *@author Amadi Ifeanyi <amadiify.com>\n**/";

                        // continue with class
                        if (class_exists($originalClass)) :

                            // create temp class and include 
                            $document[] = 'class '. basename($classWorkedOn) . ' extends \\' . $originalClass . "{}";

                            // update create
                            $create = true;

                        else:

                            if (interface_exists($originalClass)) :

                                // create interface
                                $reflection = new \ReflectionClass($originalClass);
    
                                // get file name
                                $interfaceFilename = $reflection->getFileName();

                                // read content
                                $interfaceContent = file_get_contents($interfaceFilename);

                                // get base name of original class
                                $originalClass = str_replace('\\', '/', $originalClass);

                                // base name
                                $originalClassInterface = basename($originalClass);

                                // replace interface
                                $interfaceContent = str_replace('interface '.$originalClassInterface, 'interface ' .basename($classWorkedOn), $interfaceContent);

                                // replace original class
                                $interfaceContent = str_replace($originalClassInterface, '__Compile__'.$originalClassInterface, $interfaceContent);

                                // replace interface
                                $interfaceContent = str_replace('interface __Compile__'.$originalClassInterface, 'interface ' .basename($classWorkedOn), $interfaceContent);

                                // replace namespace
                                $interfaceContent = str_replace('namespace', 'use', $interfaceContent);

                                // fix
                                $originalClass = str_replace('/', '\\', $originalClass);

                                // import this interface 
                                $interfaceContent = str_replace('<?php', "<?php\n use {$originalClass} as __Compile__{$originalClassInterface};", $interfaceContent);

                                if ($namespace != '') :
                                    
                                    $namespace = str_replace('/', '\\', $namespace);
                                    
                                    // add namespace
                                    $interfaceContent = str_replace('<?php', "<?php\n namespace {$namespace};", $interfaceContent);

                                endif;

                                // save to cache
                                $arrayData[md5($class)] = ['file' => $interfaceFilename, 'timestamp' => filemtime($interfaceFilename)];

                                // add to cache
                                file_put_contents($interfaceCacheFile, json_encode($arrayData, JSON_PRETTY_PRINT)); 

                                // save file
                                File::write($interfaceContent, $filename);

                                // include file
                                include_once $filename;

                            else:

                                if (trait_exists($originalClass)) :

                                    $document[] = 'trait '.basename($classWorkedOn) . '{ use \\'.$originalClass.'; }';

                                    // update create
                                    $create = true;

                                endif;

                            endif;

                        endif;

                        // save file
                        if ($create) :

                            // save file
                            File::write(implode("\n", $document), $filename);

                            // include file
                            include_once ($filename);

                        endif;

                    endif;

                    // break out
                    break;

                endif;

            endforeach;

            // add class not found to avoid creating multiple files.
            if ($classFound === false) self::$classNotFound[$baseClassName] = true;

        endif;

        // return boolean
        return $classFound;
    }
}