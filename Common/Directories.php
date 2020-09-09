<?php
namespace Lightroom\Common;

use Lightroom\Common\JSONHelper;
use Lightroom\Exceptions\JsonHandlerException;

/**
 * @package Directories
 * 
 * A simple directory class for file, sub directories fetching.
 * @author amadi ifeanyi <amadiify.com>
 * 
 * You can have this class extend to your class or use a trait to apply additional features
 */
class Directories
{
    use JSONHelper;

    /**
     * @var string $fileCachePath
     * MUST be a json file.
     */
    private static $fileCachePath = __DIR__ . '/directory.cache.json';

    /**
     * @var string $currentFileCachePath
     */
    private static $currentFileCachePath = null;

    /**
     * @method Directories findFileFrom a directory
     * This makes a deep scan into all directories in a parent directory, and finally returns a path or empty string
     * @param string $directory
     * @param string $file
     * @return string
     * @throws JsonHandlerException
     */
    public static function findFileFrom(string $directory, string $file) : string
    {
        // file exists in cache
        if (self::filePathPreviouslyCached($directory, $file)) :

            // get path from cache
            return self::getPathFromCache();

        endif;

        // return filepath
        $filepath = self::useDeepScanner($directory, $file);

        // deep scanner success event if file was found
        if ($filepath != '') :

            // fire event
            self::deepScannerEvent('success', [
                'path' => $filepath,
                'directory' => $directory,
                'file' => $file
            ]);

        endif;

        return $filepath;
    }

    /**
     * @method Directories useDeepScanner method
     * @param string $directory
     * @param string $file
     * @return mixed|string
     */
    public static function useDeepScanner(string $directory, string $file)
    {
        $filepath = '';

        // get files and directories from $directory at this level
        $filesAndFolders = glob($directory . '/*');

        // run foreach loop, but ensure $filesAndFolders is an array
        if (is_array($filesAndFolders)) :

            foreach ($filesAndFolders as &$fileOrDirectory) :

                // skip checking from . and .. 
                if ($fileOrDirectory != '.' && $fileOrDirectory != '..') :

                    // check if basename matches $file basename
                    if (!is_dir($fileOrDirectory) && (basename($file) == basename($fileOrDirectory))) :

                        // stop here
                        $filepath = $fileOrDirectory;

                    endif;

                    if ($filepath == '' && is_dir($fileOrDirectory)) :

                        // check method again and terminate if $filepath is not empty
                        $filepath = self::useDeepScanner($fileOrDirectory, $file);

                        // check if path was returned
                        if ($filepath != '') :

                            if (basename($fileOrDirectory) == basename($file)) :

                                break;

                            endif;
                        
                        endif;

                    endif;

                endif;

            endforeach;

            // clean up
            unset($fileOrDirectory, $filesAndFolders);

        endif;

        return $filepath;
    }

    /**
     * @method Directories findBaseDirectory
     * @param string $parentDirectory
     * @param string $directory
     * @return string
     */
    public static function findBaseDirectory(string $parentDirectory, string $directory) : string
    {
        // get all directories in $parentDirectory root
        $directories = glob($parentDirectory . '/*');

        // empty file path
        $filepath = '';

        foreach ($directories as $dir) :
        
            if ($dir != '.' && $dir != '..' && is_dir($dir))
            {
                // get base name of directory
                $base = basename($dir);

                // is base equivalent to $directory
                if ($base == $directory)
                {
                    return $dir;
                }

                // call method again
                $filepath = self::findBaseDirectory($dir, $directory);

                if ($filepath !== '')
                {
                    return $filepath;
                }
            }

        endforeach;

        return $filepath;
    }

    /**
     * @method Directories findDirectory
     * @param string $parentDirectory
     * @param array $directory
     * @return string
     */
    public static function findDirectory(string $parentDirectory, array $directory) : string
    {
        // filepath
        $filepath = '';
        
        if (isset($directory[0])) :
        
            // get base directory
            $baseDirectory = self::findBaseDirectory($parentDirectory, $directory[0]);

            // base directory is not an empty string
            // we get file path
            if ($baseDirectory != null) :
            
                // remove index
                $baseIndex = $directory[0];
                $quote = preg_quote('/'.$baseIndex, '/');
                $baseDirectory = preg_replace("/($quote)$/", '', $baseDirectory);
                $parentDirectory = $baseDirectory . '/' . implode('/', $directory);

                if (is_dir($parentDirectory)) :
                
                    $filepath = $parentDirectory;

                endif;

            endif;

        endif;

        return $filepath;
    }

    /**
     * @method Directories deepScannerEvent listener
     * @param string $event
     * @param array $fileInfo
     * @throws JsonHandlerException
     */
    private static function deepScannerEvent(string $event, array $fileInfo)
    {
        switch ($event) :

            case 'success':
                // save to cache
                self::saveDirectoryFilePath($fileInfo);
            break;

        endswitch;
    }

    /**
     * @method Directories getPathFromCache
     */
    private static function getPathFromCache()
    {
        return self::$currentFileCachePath;
    }

    /**
     * @method Directories filePathPreviouslyCached
     * check if file has been cached previously to improve performance and reduce memory usage
     * @param string $directory
     * @param string $file
     * @return bool
     * @throws JsonHandlerException
     */
    private static function filePathPreviouslyCached(string $directory, string $file) : bool
    {
        // file cached ?
        $cached = false;

        // check if file cache path exists
        if (self::cachePathExists($cachepath)) :

            // check if file exists
            if (file_exists($cachepath)) :

                // get key
                $key = self::buildCacheKey($directory, $file);

                // read json
                $json = self::read_json($cachepath, true);

                // check if array has key
                if (isset($json[$key])) :

                    $cached = true;

                    // save to current file cache path
                    self::$currentFileCachePath = $json[$key];

                endif;

            endif;
            
        endif;

        // return bool
        return $cached;
    }

    /**
     * @method Directories saveDirectoryFilePath
     * save directory file information to cache
     * @param array $fileInfo
     * @throws JsonHandlerException
     */
    private static function saveDirectoryFilePath(array $fileInfo)
    {
        // check if file cache path exists
        if (self::cachePathExists($cachepath)) :

            // build closure for saving data
            $directoryClosure = function(string $path, $data)
            {   
                self::save_json($path, $data);
            };

            // get key
            $key = self::buildCacheKey($fileInfo['directory'], $fileInfo['file']);

            // create file if doesn't exists
            if (!file_exists($cachepath)) :

                // save fresh data
                $directoryClosure($cachepath, [$key => $fileInfo['path']]);

            endif;

            // read json data
            $json = self::read_json($cachepath, true);

            // add to json array
            $json[$key] = $fileInfo['path'];

            // save json data
            $directoryClosure($cachepath, $json);
            
        endif;
    }

    /**
     * @method Directories buildCacheKey
     * build cache key
     * @param string $directory
     * @param string $file
     * @return string
     */
    private static function buildCacheKey(string $directory, string $file) : string
    {
        return ($directory . '===' . $file);
    }

    /**
     * @method Directories cachePathExists
     * checks if a cache path exists
     * @param null $cachePath
     * @return bool
     */
    private static function cachePathExists(&$cachePath=null) : bool
    {
        // @var bool $pathExists
        $pathExists = false;

        // set cache path
        $cachePath = self::$fileCachePath;

        // check if file cache path exists
        if (!is_null($cachePath)) :

            if (strrpos($cachePath, '.json') !== false) :
                
                // path exits
                $pathExists = true;

            endif;

        endif;

        return $pathExists;
    }

}