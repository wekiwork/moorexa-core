<?php
namespace Lightroom\Packager\Moorexa;

use Lightroom\Common\Directories;
use Lightroom\Core\Interfaces\PrivateAutoloaderInterface;

/**
 * @package NamespaceAutoloader for Moorexa
 * @author amadi ifeanyi <amadiify.com>
 * 
 * This registers a namespace for quick namespaces.
 * example.
 * 
 * -Lab
 *  - Account
 *     - Account.php
 * 
 * We can register lab directory and access Account.php via Account\Account.php
 */
trait NamespaceAutoloader
{
    // list of namespaces
    public $namespaces = [];

    /**
     * @method PrivateAutoloaderInterface autoloaderRequested
     * This method would be called by the FrameworkAutoloader during runtime.
     * You can implement your logic in this method and must return a boolean (true | false)
     * @param string $class
     * @return bool
     */
    public function autoloaderRequested(string $class) : bool
    {
        // class found
        /** @var bool $classfound */
        $classfound = false;

        // empty file path
        $filepath = '';

        // file exists in cache
        if (self::fileAutoloadPathPreviouslyCached($class)) :

            // get path from cache
            $path = self::getAutoloadPathFromCache();

            if (file_exists($path)) :

                include_once $path;

                // class found
                $classfound = true;

            endif;

        endif;
        

        if ($classfound === false) :

            // check directories
            foreach ($this->namespaces as $namespace => $directory) :
                
                // get namespace and a copy of it
                list($namespace, $namespaceCopy) = $this->getNamespaceAndACopy($namespace, $class);

                // get class string
                // convert \ to / in class
                $classString = str_replace('\\', '/', $class);

                // do we have a match?
                if (strcmp($namespace, $namespaceCopy) === 0) :
                
                    // from the config file, a directory can contain an array mapped to a specific class and method.
                    if (is_array($directory)) :
                    
                        // call the method from within that class and pass the $classString as an argument
                        // method in class must be a static method
                        $filepath = call_user_func_array($directory, [$classString]);

                        // include path if returned.
                        if (!is_null($filepath) && file_exists($filepath)) :
                        
                            include_once $filepath;

                            // update class found var
                            $classfound = true;

                        endif;
                    
                    else:

                        // get basename
                        $file = basename($classString) . '.php';

                        $filepath = Directories::findFileFrom($directory, $file);

                        if (strlen($filepath) > 2 && file_exists($filepath)) :
                        
                            // include path
                            include_once $filepath;

                            // update class found var
                            $classfound = true;

                        endif;
                    
                    endif;
                
                endif;

            endforeach;

            // directory autoload was a success, fire event if $filepath is not empty
            if ($filepath != '') :

                self::autoloaderCachingEvent('success', [
                    'path' => $filepath,
                    'class' => $class
                ]);

            endif;

        endif;

        // return bool
        return $classfound;
    }

    /**
     * @method NamespaceAutoloader getNamespaceAndACopy
     * @param string $namespace
     * @param string $class
     * @return array
     */
    private function getNamespaceAndACopy(string $namespace, string $class) : array 
    {
        // remove asterisk
        $namespace = rtrim($namespace, '*');
        $namespaceAsPath = str_replace("/", '\\', implode('\\', explode('\\', $class)));
        $namespaceCopy = $namespaceAsPath . '\\';

        // remove trailing backward slash
        $namespace = rtrim($namespace, "\\");

        // convert namespace to array
        $namespaceArray = explode("\\", $namespace);

        // remove trailing backward slash
        $namespaceCopy = rtrim($namespaceCopy, "\\");

        // convert namespace to array
        $namespaceCopyArray = explode("\\", $namespaceCopy);

        // get namespace array length
        $namespaceArraySize = count($namespaceArray);
        $newArray = array_splice($namespaceCopyArray, 0, $namespaceArraySize);

        $namespaceCopy = implode("\\", $newArray);

        return [$namespace, $namespaceCopy];
    }
}