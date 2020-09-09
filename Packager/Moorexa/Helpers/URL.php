<?php
namespace Lightroom\Packager\Moorexa\Helpers;

use Lightroom\Exceptions\ClassNotFound;
use Lightroom\Adapter\{
    URL as UrlTrait, ClassManager, GlobalFunctions
};
use function Lightroom\Functions\GlobalVariables\var_get;
/**
 * @package Moorexa URL Helper
 * @author Amadi Ifeanyi <amadiify.com>
 */
class URL
{
    use UrlTrait;

    /**
     * @var array $incomingURI
     */
    protected static $incomingURI = [];

    /**
     * @var bool urlRegistered
     */
    private static $urlRegistered = false;

    /**
     * @method URL registerUrlFromConfig
     * @return void
     * @throws ClassNotFound
     */
    public static function registerUrlFromConfig() : void 
    {
        if (self::$urlRegistered === false) :
            
            // @var string $url
            $url = env('bootstrap', 'app.url');

            // @var URL $urlHelper
            $urlHelper = ClassManager::singleton(static::class);

            // get url path also
            $url = $url == '' ? $urlHelper->getPathUrl() : $url;

            // set url
            if (is_string($url)) $urlHelper->setUrl($url);

            // create url function that returns a single instance of this class
            var_get('function-wrapper')->create('urlClass', function() use (&$urlHelper)
            {
                return $urlHelper;

            })->attachTo(GlobalFunctions::class);

            // registered
            self::$urlRegistered = true;

        endif;
    }

    /**
     * @method URL getIncomingUri
     * @return array
     */
    public static function getIncomingUri() : array
    {
        return self::$incomingURI;
    }

    /**
     * @method URL setIncomingUri
     * @param array $incomingURI
     * @return void
     */
    public static function setIncomingUri(array $incomingURI) : void
    {
        self::$incomingURI = $incomingURI;
    }
}