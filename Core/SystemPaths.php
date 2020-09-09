<?php
namespace Lightroom\Core;

/**
 * @package system paths
 * @author Amadi ifeanyi
 * 
 * A simple class for application paths
 */
class SystemPaths implements Interfaces\SystemPathsInterface
{
    /**
     * @var string $baseFile
     */
    private $baseFile = '';

    /**
     * @method SystemPaths set base file
     * should be a valid file path
     * @param string $baseFile
     */
    public function setBaseFile(string $baseFile)
    {
        $this->baseFile = $baseFile;
    }

    /**
     * @method SystemPaths get base file
     * should return a valid base file
     */
    public function getBaseFile() : string
    {
        return $this->baseFile;
    }

    /**
     * @method SystemPaths load path
     * this method would handle path loading
     */
    public function loadPath()
    {
        // get baseFile
        $baseFile = $this->getBaseFile();

        // include path
        if (!file_exists($baseFile)) :
            
            throw new \Lightroom\Exceptions\FileNotFound($baseFile);

        endif;

        include_once $baseFile;

        // clean up
        unset($baseFile);
    }
}