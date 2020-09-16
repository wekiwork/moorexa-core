<?php

use Lightroom\Core\{
    FunctionWrapper, GlobalConstants
};
use Lightroom\Adapter\{
    GlobalFunctions, ClassManager
};
use function Lightroom\Functions\GlobalVariables\var_set;

/**
 * @package Function Library for the application framework
 */

// instance of function wrapper
// load global functions with path in $globalfunc variable 
/** @var string $globalfunc */
$function = var_set('function-wrapper', ClassManager::singleton(FunctionWrapper::class, $globalfunc));

/**
 * @method GlobalConstants log
 * 
 * create logger switch function
 * this function by default, would return the default logger
 * you can pass a logger name to make a quick switch.
 */
$function->create('const', function(string $constantName)
{   
    // return constant values
    return GlobalConstants::getConstant($constantName);

})->attachTo(GlobalFunctions::class);

/**
 * @method DeepScanner
 * 
 * Searches for a file in a top to down pattern
 */
$function->create('deepscan', function(string $directory, string $file) : string
{
    // returned file path
    $filepath = file_exists($directory . '/' . $file) ? ($directory . '/' . $file) : '';

    // file path is empty
    if ($filepath == '') :

        // build path
        $filepath = Lightroom\Common\Directories::findFileFrom($directory, $file);

    endif;

    // return string
    return $filepath;
        
})->attachTo(GlobalFunctions::class);

/**
 * @method toArray
 * @param object $object
 * Converts an object to an array
 * @return array
 */
$function->create('toArray', function(object $object) : array
{
    return json_decode(json_encode($object), true);

})->attachTo(GlobalFunctions::class);

/**
 * @method toObject
 * @param array $array
 * Converts an array to an object
 * @return object
 */
$function->create('toObject', function(array $array) : object
{
    // build an empty object
    $object = (object) [];

    // get value and key from array
    foreach ($array as $index => $value) :
        
        // save to empty object
        $object->{$index} = $value;

        // value is an array. we seek deeper
        if (is_array($value)) :
        
            // convert value to an object
            $value = func()->toObject($value);

            // save to empty object
            $object->{$index} = (object) $value;
    
        endif;

    endforeach;
    
    // return object
    return $object;
    
})->attachTo(GlobalFunctions::class);

/**
 * @method readXml
 * @param string $xmlPath
 * @return object
 */
$function->create('readXml', function(string $xmlPath)
{
    // load xml file
    $xml = simplexml_load_file($xmlPath);

    // @var string $xmlString
    $xmlString = str_replace('@attributes', 'attr', json_encode($xml));

    // find attribute
    preg_match_all('/["]+(attr)+["]+[:]([^}]+)+[}]/', $xmlString, $matches);

    if (count($matches[0]) > 0) :
    
        foreach ($matches[0] as $xmlChild) :
        
            // update xmlString
            $xmlString = str_replace($xmlChild, rtrim(str_replace('"attr":{', '', $xmlChild), "}"), $xmlString);

        endforeach;

    endif;

    // return object
    return json_decode($xmlString);

})->attachTo(GlobalFunctions::class);

/**
 * @method reduce_array
 * @param array $array
 * @return array
 * 
 * Reduce a multi dimentional array
 */
$function->create('reduce_array', function(array $array) : array
{
    // create recursive function
    func()->create('__reduceArray', function(array $array, array $newArray) : array
    {
        foreach ($array as $arrayKey => $arrayValue) :
        
            if (!is_array($arrayValue)) :
                
                // update new array
                $newArray[$arrayKey] = $arrayValue;
            
            else:
            
                foreach($arrayValue as $arrayChildKey => $arrayChildValue) :
                
                    if (!is_array($arrayChildValue)) :
                    
                        // update new array
                        $newArray[$arrayChildKey] = $arrayChildValue;
                    
                    else:
                        
                        // reduce one step down
                        $newArray = func()->__reduceArray($arrayChildValue, $newArray);

                    endif;

                endforeach;

            endif;

        endforeach;

        // return array
        return $newArray;

    })->attachTo(GlobalFunctions::class);

    // return array
    return func()->__reduceArray($array, []);
    
})->attachTo(GlobalFunctions::class);

/**
 * @method is_disabled
 * @param string $function 
 * @return bool
 */
$function->create('is_disabled', function(string $function) : bool
{   
    // get disabled functions
    $disabled = explode(',', ini_get('disable_functions'));

    // return bool
    return in_array($function, $disabled) ? true : false;

})->attachTo(GlobalFunctions::class);

/**
 * @method timeAgo
 * @param string|int $time
 * @return string
 */
$function->create('timeAgo', function($time) : string 
{   
    if ((is_string($time) && strlen($time) == 0)) return '';

    // continue
    if (!is_null($time)) :

        $timeInt = intval($time);

        if (preg_match('/[\D]/', $time) == false) :

            // update time
            $time = $timeInt;

        else:

            $time = strtotime($time);

        endif;

        // @var string $time_difference
        $time_difference = time() - $time;

        if( $time_difference < 1 ) return 'less than 1 second ago';

        // @var array $condition
        $condition = array( 12 * 30 * 24 * 60 * 60 =>  'year',
                    30 * 24 * 60 * 60       =>  'month',
                    24 * 60 * 60            =>  'day',
                    60 * 60                 =>  'hour',
                    60                      =>  'minute',
                    1                       =>  'second'
        );

        // get time difference from condition
        foreach( $condition as $secs => $str ) :
        
            $d = $time_difference / $secs;

            if( $d >= 1 ) :
            
                $t = round( $d );
                return $t . ' ' . $str . ( $t > 1 ? 's' : '' ) . ' ago';

            endif;

        endforeach;

    endif;

    // no string
    return '';

})->attachTo(GlobalFunctions::class);

/**
 * @method createTmpFile
 * @param string $file 
 * @return array
 * @throws Exception
 */
$function->create('createTmpFile', function(string $file) : array 
{
    // get tmp directory
    $directory = sys_get_temp_dir();

    // fail if file does not exists
    if (!file_exists($file)) throw new Exception('Creating Tmp file from #{'.$file.'} failed because file doesn\'t exists.');

    //  create tmp file
    $tmpfile = tempnam($directory, md5($file));

    // save file body
    file_put_contents($tmpfile, file_get_contents($file));

    // return file array
    return [

        'name' => basename($file),
        'tmp_name' => $tmpfile,
        'type' => mime_content_type($file),
        'size' => filesize($tmpfile),
        'error' => 0
    ];


})->attachTo(GlobalFunctions::class);

/**
 * @method finder 
 * @param string $config
 * @return mixed
 */
$function->create('finder', function(string $config) {

    // create a static class
    static $class;

    if ($class === null) :

        $class = new class(){
            
            use \Lightroom\Packager\Moorexa\Helpers\RouterControls;

            /**
             * @method class find
             * @param string $config 
             * @return mixed
             */
            public function find(string $config)
            {
                return self::readConfig($config);
            }
        };

    endif;

    // return class
    return $class->find($config);

})->attachTo(GlobalFunctions::class);

/**
 * @method trueOnly
 * @param array $list
 * @return bool
 * 
 * This function would return true if everything in the list returns true
 */
$function->create('trueOnly', function(array $list) : bool 
{   
    // @var bool $default
    $default = true;

    // check now
    foreach ($list as $bool) :

        // are we good ??
        if ($bool !== true) $default = false;
        
    endforeach;

    // return bool
    return $default;
    
})->attachTo(GlobalFunctions::class);