<?php
namespace Lightroom\Database\Cache;

class QueryCache
{
    /**
     * @var array $cacheArray
     */
    public static $cacheArray = [];

    /**
     * @method QueryCache isCached
     * @param string $method
     * @param array $arguments
     * @param mixed $cacheArray (reference)
     * @return bool
     */
    public static function isCached(string $method, array $arguments, &$cacheData = []) : bool 
    {
        // @var bool $cached 
        $cached = false;

        // can we check
        if (env('bootstrap', 'enable.db.caching') === true) :
            
            // start buffer
            ob_start();

            // dump value
            debug_zval_dump($arguments);

            // get content
            $content = md5(ob_get_contents());

            // clean buffer
            ob_end_clean();

            // check if cached
            $file = __DIR__ . '/Dumps/'.$method.'.php';

            // continue if file exists
            if (file_exists($file)) :

                // import array
                $cacheArray = include_once $file;

                // merge with global
                $cacheArray = is_array($cacheArray) ? array_merge($cacheArray, self::$cacheArray) : self::$cacheArray;

                // query cached?
                if (is_array($cacheArray) && isset($cacheArray[$content])) :

                    // cached
                    $cached = true;

                    // push data
                    $cacheData = $cacheArray[$content];

                else:

                    // create closure
                    $cacheData = function(string $query, array $bind) use ($content, $file, $cacheArray)
                    {
                        // build new data
                        $data = [$content => [
                            'query' => $query,
                            'bind' => $bind
                        ]];

                        // merge with cacheArray
                        $cacheArray = is_array($cacheArray) ? array_merge($cacheArray, $data) : $data;

                        // make global
                        QueryCache::$cacheArray = $cacheArray;

                        // save data
                        if (is_writable($file)) :

                            // export data
                            ob_start();

                            // dump data
                            var_export($cacheArray);

                            // get content
                            $body = ob_get_contents();

                            // clear buffer
                            ob_end_clean();

                            // save data
                            file_put_contents($file, "<?php\n\nreturn {$body};\n");

                        endif;
                    };


                endif;

            endif;
            
        
        endif;

        // return bool
        return $cached;
    }
}