<?php
namespace Lightroom\Packager\Moorexa;

/**
 * @package autoloader caching system
 * @author amadi ifeanyi <amadiify.com>
 * @author fregatelab <fregatelab.com>
 * 
 * Requires that you use JSONHandler trait
 */
trait AutoloaderCachingSystem
{
    // autoload var
    public static $currentautoloadfilecachepath = null;

    // fileautoloadcachepath
    private static $fileautoloadcachepath = __DIR__ . '/autoload.cache.json';

    /**
     * @method DirectoryAutoloader getAutoloadPathFromCache
     */
    private static function getAutoloadPathFromCache()
    {
        return self::$currentautoloadfilecachepath;
    }

    /**
     * @method DirectoryAutoloader filePathPreviouslyCached
     * check if file has been cached previously to improve performance and reduce memory usage
     * @return bool
     * @param string $class
     */
    private static function fileAutoloadPathPreviouslyCached(string $class) : bool
    {
        // file cached ?
        $cached = false;

        // check if file cache path exists
        if (self::cacheAutoloadPathExists($cachepath)) :

            // check if file exists
            if (file_exists($cachepath)) :

                // get key
                $key = self::buildAutoloadCacheKey($class);

                // read json
                $json = self::read_json($cachepath, true);

                // check if array has key
                if (isset($json[$key])) :

                    $cached = true;

                    // save to currentautoloadfilecachepath
                    self::$currentautoloadfilecachepath = $json[$key];

                endif;

            endif;
            
        endif;

        // return bool
        return $cached;
    }

    /**
     * @method DirectoryAutoloader saveAutoloadedFilePath
     * save class | trait | interface file information to cache
     * @param array $fileInfo
     */
    private static function saveAutoloadedFilePath(array $fileInfo)
    {
        // check if class cache path exists
        if (self::cacheAutoloadPathExists($cachepath)) :

            // build closure for saving data
            $autoloadClosure = function(string $path, $data)
            {   
                self::save_json($path, $data);
            };

            // get key
            $key = self::buildAutoloadCacheKey($fileInfo['class']);

            // create file if doesn't exists
            if (!file_exists($cachepath)) :

                // save fresh data
                $autoloadClosure($cachepath, [$key => $fileInfo['path']]);

            endif;

            // read json data
            $json = self::read_json($cachepath, true);

            // add to json array
            $json[$key] = $fileInfo['path'];

            // save json data
            $autoloadClosure($cachepath, $json);

            // clean up
            unset($autoloadClosure, $json, $key);
            
        endif;
    }

    /**
     * @method AutoloaderCachingSystem buildAutoloadCacheKey
     * build cache key
     * @param string $class
     * @return string
     */
    private static function buildAutoloadCacheKey(string $class) : string
    {
        return ($class . '/' . md5($class));
    }

    /**
     * @method AutoloaderCachingSystem cacheAutoloadPathExists
     * checks if a cache path exists
     * @param null $cachePath
     * @return bool
     */
    private static function cacheAutoloadPathExists(&$cachePath=null) : bool
    {
        // @var bool $pathExists
        $pathExists = false;

        // set cache path
        $cachePath = self::$fileautoloadcachepath;

        // check if file cache path exists
        if (!is_null($cachePath)) :

            if (strrpos($cachePath, '.json') !== false) :
                
                // path exits
                $pathExists = true;

            endif;

        endif;

        return $pathExists;
    }

    /**
     * @method AutoloaderCachingSystem autoloaderCachingEvent listener
     * @param string $event
     * @param array $fileInfo
     */
    private static function autoloaderCachingEvent(string $event, array $fileInfo)
    {
        switch ($event) :

            case 'success':
                // save to cache
                self::saveAutoloadedFilePath($fileInfo);
            break;

        endswitch;
    }
}