<?php
namespace Lightroom\Packager\Moorexa;

use Lightroom\Packager\Moorexa\{
    BootloaderPrivates, DirectoryAutoloader, AutoloaderCachingSystem
};
use Lightroom\{Core\Interfaces\PrivateAutoloaderInterface,
    Core\FrameworkAutoloader,
    Common\JSONHelper,
    Exceptions\MoorexaInvalidEnvironmentFile};
/**
 * @package BootloaderConfiguration class
 * @author fregatelab <fregatelab.com>
 */
class BootloaderConfiguration
{
    use BootloaderPrivates;

    /**
     * @method BootloaderConfiguration loadBootstrap
     * Contains an array of configuration
     * @param array $bootstrap
     * @throws MoorexaInvalidEnvironmentFile
     */
    public function loadBootstrap(array $bootstrap)
    {
        // read environment var
        $this->readEnvironmentVariables($bootstrap);

        // save to environment with key 'bootstrap'
        $this->saveToEnvironment('bootstrap', $bootstrap);
    }

    /**
     * @method BootloaderConfiguration loadFinder
     * finder contains an array of settings for quick access to files inside a directory or in a namespace.
     * @param array $finder
     */
    public function loadFinder(array $finder)
    {
        // register autoloader directories
        $this->registerAutoloaderDirectories($finder);

        // register autoloader namespaces
        $this->registerAutoloaderNamespaces($finder);
    }

    /**
     * @method BootloaderConfiguration registerAutoloaderDirectories
     * 
     * enable quick access to files by registering a directory
     * @param array $finder reference
     */
    private function registerAutoloaderDirectories(array &$finder)
    {
        $autoloader = isset($finder['autoloader']) ? $finder['autoloader'] : false;

        if ($autoloader !== false) :

            // create an anonymous class
            $autoloaderClass = new class() implements PrivateAutoloaderInterface 
            { 
                use DirectoryAutoloader; 
                use AutoloaderCachingSystem;
                use JSONHelper;
            };

            // register directories
            $autoloaderClass->directories = $autoloader;

            // push to FrameworkAutoloader
            FrameworkAutoloader::registerPrivateAutoloader($autoloaderClass);

            // clean up
            unset($autoloaderClass, $autoloader);

        endif;
    }

    /**
     * @method BootloaderConfiguration registerAutoloaderNamespaces
     * 
     * enable quick access to files by registering a namespace
     * @param array $finder reference
     */
    private function registerAutoloaderNamespaces(array &$finder)
    {
        $namespaces = isset($finder['namespacing']) ? $finder['namespacing'] : false;

        if ($namespaces !== false) :

            // create an anonymous class
            $namespaceClass = new class() implements PrivateAutoloaderInterface 
            { 
                use NamespaceAutoloader; 
                use AutoloaderCachingSystem;
                use JSONHelper;
            };

            // register namespaces
            $namespaceClass->namespaces = $namespaces;

            // push to FrameworkAutoloader
            FrameworkAutoloader::registerPrivateAutoloader($namespaceClass);

            // clean up
            unset($namespaceClass, $namespaces);

        endif;
    }

}