<?php
namespace Lightroom\Common;

use function Lightroom\Functions\GlobalVariables\var_set;

/**
 * @package Include injector
 * @author fregatelab <fregatelab.com>
 * 
 * This trait will load a class and inject that class to a group of files or classes
 */
trait IncludeInjector
{
    // @var array $classFiles
    private $classFiles = [];

    // @var string $classVariableName
    private $classVariableName = '';

    // @var array $exportVariables
    private $exportVariables = [];

    /**
     * @method IncludeInjector loadClass
     * Load a class
     * @param string $className
     * @param $injector
     */
    public function loadClass(string $className, $injector)
    {
        // get class instance
        $classInstance = new $className;
        $injector->injectFilesWithInstance($classInstance);

        // clean up
        unset($injector, $classInstance);
    }

    /**
     * @method IncludeInjector injectFilesWithInstance
     * inject files with class instance
     * @param $classInstance
     */
    public function injectFilesWithInstance($classInstance)
    {
        // get variable name
        $variable = $this->getVarName();

        // get inject files
        $filesClosure = function($variable, $classInstance)
        {
            // get import files
            $importFiles = $this->getImportFiles();

            // inject files
            foreach ($importFiles as &$filePath) :
            
                // set class instance
                ${$variable} = $classInstance;

                // add php extension or ignore
                $filePath = strrpos($filePath, '.php') === false ? $filePath . '.php' : $filePath;

                // include if path exists
                if (file_exists($filePath)) :

                    // extract exported variables
                    extract($this->exportVariables);

                    // include file
                    include_once $filePath;
                
                endif;
                
            endforeach;

            // save variable to glob
            var_set($variable, $classInstance);

            // clean up
            unset($importFiles, $filePath, $classInstance);
        };

        call_user_func($filesClosure->bindTo($this, static::class), $variable, $classInstance);

        // clean up
        unset($filesClosure);
    }

    /**
     * @method IncludeInjector import
     * inject files into a class scope
     * @param array $files
     * @return IncludeInjector
     */
    public function import(array $files)
    {
        $this->classFiles = $files;

        // clean up
        unset($files);

        // return class instance
        return $this;
    }

    /**
     * @method IncludeInjector varName
     * create a local variable for class instance. This variable would be visible to class instance
     * @param string $varName
     * @return IncludeInjector
     */
    public function varName(string $varName)
    {   
        $this->classVariableName = $varName;
        return $this;
    }

    /**
     * @method IncludeInjector getVarName
     * get local variable name
     */
    public function getVarName()
    {
        return $this->classVariableName;
    }

    /**
     * @method IncludeInjector getImportFiles
     * get imported files array
     */
    public function getImportFiles()
    {
        return $this->classFiles;
    }

    /**
     * @method IncludeInjector export
     * export variables into a file
     * @param array $variables
     * @return IncludeInjector
     */
    public function export(array $variables)
    {
        $this->exportVariables = $variables;
        return $this;
    }
}