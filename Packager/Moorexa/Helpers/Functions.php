<?php

use Lightroom\Adapter\{
    GlobalFunctions, ClassManager
};
use Lightroom\Packager\Moorexa\Helpers\URL;
use function Lightroom\Functions\GlobalVariables\var_get;

// @var function wrapper
$function = var_get('function-wrapper');

/**
 * @method url
 * @param string $path
 * @return string
 * 
 * This function returns the current app url
 */
$function->create('url', function(string $path = '') : string 
{
    // get url from adapter
    $urlHandler = ClassManager::singleton(URL::class);

    // return string
    return rtrim($urlHandler->getUrl(), '/') . '/' . ltrim($path, APPLICATION_ROOT);

})->attachTo(GlobalFunctions::class);

/**
 * @method extension
 * @param string $file
 * @return string
 * 
 * This function returns the extension of a file
 */
$function->create('extension', function(string $file) : string 
{
    // @var string $extension
    $extension = strrpos($file, '.');
    $extension = substr($file, $extension+1);

    // return string
    return $extension;

})->attachTo(GlobalFunctions::class);
