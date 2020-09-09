<?php
namespace Lightroom\Core\Interfaces;

/**
 * @package System path inteface
 */
interface SystemPathsInterface
{
    /**
     * @method SystemPathsInterface set base file
     * should be a valid file path
     * @param string $base
     */
    public function setBaseFile(string $base);

    /**
     * @method SystemPathsInterface get base file
     * should return a valid base file
     */
    public function getBaseFile() : string;

    /**
     * @method SystemPathsInterface load path
     * this method would handle path loading
     */
    public function loadPath();

}