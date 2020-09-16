<?php
namespace Lightroom\Database\Cache;

use Lightroom\Database\DatabaseHandler as Handler;

/**
 * @package FileQueryCache
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 */
trait FileQueryCache
{
    // get query cache path
    public $queryCachePath = null;

    // driver source
    public $driverSource = '';

    // cached data array for migration
    private static $cacheQueryData = [];

    // allow saving queries
    private $allowSaveQuery = true;

    // allow query caching
    private $cacheQuery = true;

    /**
     * @method FileQueryCache runSaveCacheStatements
     * @param string $tableName
     * @param string $handler
     * @return void
     * 
     * This method facilitates migration from cached queries in a path
     */
    public function runSaveCacheStatements(string $tableName, string $handler) : void
    {
        // this would be called by the cli
        if (defined('RUN_MIGRATION')) :
        
            // get path
            $path = $this->getQuerySavePath($handler);

            // check if cache file exists
            if (file_exists($path)) :
            
                // get cache array
                if (count(self::$cacheQueryData) == 0) :
                
                    // set data
                    $data = include_once($path);

                    // push array
                    if (is_array($data)) self::$cacheQueryData = $data;

                endif;

                // get data
                $data = self::$cacheQueryData;

                // check if we have any cache for database source
                if (isset($data[$handler])) :
                
                    // get array of cached data
                    $dataHandler = $data[$handler];

                    // check for table caches
                    if (isset($dataHandler[$tableName])) :
                    
                        // get queries
                        $queries = array_values($dataHandler[$tableName]);

                        // set table
                        $this->table = $tableName;
                        $this->cacheQuery = false;

                        // run queries
                        foreach ($queries as $key => $data) :
                        
                            $this->query = $data['query'];
                            $this->bind = $data['bind'];

                            // get query
                            preg_match('/^(update|insert|delete|select)/i', trim($this->query), $match);

                            // add method
                            if (isset($match[0])) $this->method = strtolower($match[0]);

                            // execute query
                            $this->___execute($this->___prepare($data['query']));

                        endforeach;

                    endif;

                endif;

            endif;

        endif;
    }

    /**
     * @method FileQueryCache getQuerySavePath
     * @param string $source
     * @return string
     * 
     * This method returns a cache query path.
     */
    private function getQuerySavePath(string $source = '') : string
    {
        // get source
        $source = strlen($source) == 0 ? $this->driverSource : $source;

        // return query cache path
        if (!is_null($this->queryCachePath)) return $this->queryCachePath;

        // create hash
        $hash = md5($source) . '.php';

        // return base path
        return get_path(func()->const('database'), '/Sql/' . $hash);
    }

    /**
     * @method FileQueryCache saveQueryStatement
     * @param string $query
     * @param array $bind
     * @return void
     * 
     * This method saves a query to a path if allowed.
     */
    private function saveQueryStatement(string $query, array $bind) : void
    {
        // allowed to save query ?
        if ($this->allowSaveQuery) :
            
            // db caching allowed ?
            if (env('bootstrap', 'enable.db.caching') && $this->cacheQuery) :
            
                // get driver source
                $source = $this->driverSource;

                // get path
                $path = $this->getQuerySavePath();

                $line = [];
                $line[] = '<?php';
                $line[] = 'return [];';
                $line[] = '?>';

                // create file
                if (!file_exists($path)) file_put_contents($path, implode("\n\n", $line));

                // get data
                $data = include($path);

                // create array
                if (!is_array($data)) $data = [];

                // build index
                $index = md5($query) . sha1(implode('', $bind));

                // add data
                $data[$source][$this->table][$index]['query'] = $query;
                $data[$source][$this->table][$index]['bind'] = $bind;
                
                // export
                ob_start();
                var_export($data);
                $data = ob_get_contents();
                ob_end_clean();

                // add to line
                $line[1] = 'return '.$data.';';

                // save now
                file_put_contents($path, implode("\n\n", $line));

            endif;

        endif;
    }
}