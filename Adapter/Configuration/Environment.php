<?php
namespace Lightroom\Adapter\Configuration;

use Lightroom\Adapter\Configuration\{Interfaces\ConfigurationSocketInterface,
    Interfaces\FunctionAttachments, Interfaces\SetSocketArrayInterface,
    Interfaces\EnvironmentInterface
};
/**
 * @package Environment Variables
 * @author fregatelab <fregatelab.com>
 */
class Environment implements ConfigurationSocketInterface, FunctionAttachments, SetSocketArrayInterface, EnvironmentInterface
{
    use ConfigurationSocket, FunctionAttachment;

    /**
     * @var array $environment_vars
     */
    private static $environment_vars = [];

    /**
     * @var array $accessed_environment_vars
     */
    private static $accessed_environment_vars = [];

    /**
     * @method Environment setEnv
     * @param string $key
     * @param mixed $value
     * Set a key, value to environment
     * @return void
     */
    public static function setEnv(string $key, $value) : void
    {
        // get $accessEnv_key
        $accessEnv_key = $key . ':';

        // save to env
        $_ENV[$key] = $value;

        // check if forward slash was used
        if (strpos($key, '/') !== false) :

            // read the key as groups
            $groups = explode('/', $key);

            // update accessed_environment_vars
            $accessEnv_key = implode(':', $groups);

            // get key and keyValue
            list($key, $keyValue) = self::generateEnvironmentVar($groups, $value);

            // add to environment vars if $key exists
            if (isset(self::$environment_vars[$key])) :

                // merge data only if $keyValue is an array
                self::$environment_vars[$key] = array_merge(self::$environment_vars[$key], $keyValue);

            else :
                // add to environment var if $key doesn't exists
                self::$environment_vars[$key] = $keyValue;

            endif;

        else :
            // update environment_vars
            self::$environment_vars[$key] = $value;  
        endif;

        // update accessed_environment_vars
        if (isset(self::$accessed_environment_vars[$accessEnv_key])) :

            // update value
            self::$accessed_environment_vars[$accessEnv_key] = $value;

        endif;
    }

    /**
     * @method Environment getEnv
     * return value from environment vars for a key.
     * @param string $key
     * @param null $value
     * @return mixed
     */
    public static function getEnv(string $key, $value = null)
    {
        // @var null $environmentVar
        $environmentVar = [];

        // check if key exists
        if (isset(self::$environment_vars[$key])) :

            // get value
            $environmentVar = self::$environment_vars[$key];

            // access value directly
            if (isset(self::$environment_vars[$key][$value])) :

                $environmentVar = self::$environment_vars[$key][$value];

            else:

                // check if key => value has been accessed previously,
                // if yes, then return value from cache
                if (self::envAccessedPreviously($key, $value)) :

                    // return value from self::$accessed_environment_vars
                    return self::$accessed_environment_vars[$key.':'.$value];
                
                endif;

                // get if environment var for array of key
                $environmentVar = self::getEnvForArray($value, $environmentVar);

                // save to accessed_environment_vars
                self::$accessed_environment_vars[$key.':'.$value] = $environmentVar;

            endif;

        else:

            // get from $_ENV
            $environmentVar = isset($_ENV[$key]) ? $_ENV[$key] : null;

            // check if env returned an array
            if (is_array($environmentVar)) :
                // check for value
                if ($value !== null && isset($environmentVar[$value])) $environmentVar = $environmentVar[$value];
            endif;
        endif;

        // check from

        return $environmentVar;
    }

    /**
     * @method Environment envAccessedPreviously
     * @param string $key
     * @param $value
     * @return bool
     */
    private static function envAccessedPreviously(string $key, $value)
    {
        // accessed
        $accessed = false;

        // check if accessed previously
        if (isset(self::$accessed_environment_vars[$key.':'.$value])) :

            $accessed = true;

        endif;

        // return bool
        return $accessed;
    }

    /**
     * @method Environment getEnvForArray
     * @param mixed $value
     * @param mixed $environmentVar
     * @return mixed
     */
    public static function getEnvForArray($value, $environmentVar)
    {
        // nest into data returned if value is not null
        // and environment var is an array
        if (!is_null($value) && is_array($environmentVar)) :

            // check if val exists as a key inside environment var
            if (isset($environmentVar[$value])) :

                // get value from environment var returned.
                $environmentVar = $environmentVar[$value];

            else:

                // check if we have pointer > 
                if (strpos($value, '>') !== false) :

                    // convert $value to an array
                    $valueArray = explode('>', $value);

                    foreach ($valueArray as &$innerChild) :
                        
                        // trim whitespaces
                        $innerChild = trim($innerChild);
                        $environmentVar = isset($environmentVar[$innerChild]) ? $environmentVar[$innerChild] : $environmentVar;

                    endforeach;

                    // clean up
                    unset($innerChild);

                endif;
                
            endif;

        endif;  
            
        return $environmentVar;
    }

    /**
     * @method generateEnvironmentVar
     * @param array $groups
     * @param mixed $value
     * @return array
     */
    private static function generateEnvironmentVar(array $groups, $value) : array
    {
        // create a local copy
        $environment_vars = [];

        // update environment_vars
        foreach ($groups as $index => $group) :

            if ($index != count($groups)-1) :
                // get the environment var at this level
                $environment_vars[$group]  = [];
            else:
                // add the value
                $environment_vars[$group] = $value;
            endif;

        endforeach;

        // get size
        $sizeofArray = count($environment_vars)-1;

        // get keys
        $arrayKeys = array_keys($environment_vars);

        // combine to one array
        for ($index = $sizeofArray; $index != 0; $index--) :

            // get previous element key
            $previous = $arrayKeys[$index-1];

            // get my key
            $myKey = $arrayKeys[$index];

            // add my data to the previous child
            $environment_vars[$previous][$myKey] = $environment_vars[$myKey];

            // unset me
            unset($environment_vars[$myKey], $myKey);

        endfor;

        // get the root index 
        $rootIndex = $arrayKeys[0];

        // return array
        return [$rootIndex, $environment_vars[$rootIndex]];
    }
}