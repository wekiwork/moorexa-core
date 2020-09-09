<?php
namespace Lightroom\Common;

use Lightroom\Common\File;
use Lightroom\Exceptions\JsonHandlerException;
/**
 * @package JSON Helper trait
 * This is a small package that can read, write and print json data from (array | file)
 */
trait JSONHelper
{
    /**
     * @method JSONHelper read json from path
     * @param string $path
     * @param bool (switch)
     * @return mixed
     * @throws JsonHandlerException
     */
    private static function read_json(string $path, bool $toArray = false)
    {
        // json found
        $json = ($toArray) ? [] : (object) [];

        // check if file exists
        $fileExists = File::exists($path, function($data) use ($toArray, &$json)
        {
            // ensure of a proper format
            if (substr($data, 0,1) == "{" && strlen($data) > 3) :
            
                // return json object | array
                $json = ($toArray) ? (array) json_decode($data) : json_decode($data);
            
            endif;
        });

        if ($fileExists === false) :

            // throw exception
            throw new JsonHandlerException($path . 'doesnt exists. Failed to read json data.');

        endif;

        return $json;
    }

    /**
     * @method JSONHelper save json data to file
     * @param string $path
     * @param mixed $data
     * @throws JsonHandlerException
     */
    private static function save_json(string $path, $data)
    {
        // convert object to array if object
        $data = is_object($data) ? ((array) $data) : $data;

        // get decoded string
        $decodedJson = json_encode($data, JSON_PRETTY_PRINT);

        if (!File::write($decodedJson, $path)) :
            
            // throw exception
            throw new JsonHandlerException($path . 'isn\'t writable or doesnt exists. Failed to save json data');

        endif;
    }
}
