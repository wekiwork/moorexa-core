<?php
namespace Lightroom\Adapter\Configuration\Interfaces;

interface URLInterface
{
    /**
     * @method URLInterface set current url
     * @param string $url
     */
    public function setUrl(string $url);

    /**
     * @method URLInterface get current url
     */
    public function getUrl() : string;

    /**
     * @method URLInterface get current url for file paths
     */
    public function getPathUrl() : string;
}