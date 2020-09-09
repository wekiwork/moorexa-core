<?php
namespace Lightroom\Database;

use closure;

/**
 * @package Table Prefixing
 * @author Amadi Ifeanyi
 */
trait TablePrefixing
{
    // hold prefix
    public $prefix = '';
    
    // no prefix ?
    public $noPrefix = false;

    // prefixed registered
    private static $prefixRegistered = [];

    // register all prefix event queries
    private static $prefixRegistry = [];

    /**
     * @method TablePrefixing getTableName
     * @param string $table
     * @return string
     * 
     * Gets a table name and prepend prefix if registered in the configuration file
     */
    public function getTableName(string $table) : string
    {
        // allowed to check for prefix
        if ($this->noPrefix === false) :
        
            // @var string prefix
            $prefix = $this->prefix;

            if ($prefix != '') :
        
                // check if prefix exists in table name
                $quote = preg_quote($prefix);

                // return table name without prefix
                if (preg_match("/($quote)/", $table)) return $table;

            endif;

            // get prefix
            $prefix = $this->getPrefix();

            // add quote to prefix
            $quote = preg_quote($prefix);

            // check table to be sure table name doesn't have the prefix already
            if (preg_match("/($quote)/", $table) == false) :
                
                // check if prefix has been registered
                if (count(self::$prefixRegistered) > 0) :
                
                    foreach (self::$prefixRegistered as $prefixRegistered) :
                    
                        // quote registered prefix
                        $quote = preg_quote($prefixRegistered);

                        //check if prefix is not contained in table name already
                        if (preg_match("/($quote)/", $table)) return $table;
 
                    endforeach;
                
                endif;

                // return prefix appended to table name
                return $prefix . $table;
            
            endif;
        
        endif;

        // return table name
        return $table;
    }

    /**
     * @method TablePrefixing getPrefix
     * @return string
     * 
     * This method returns a prefix from the current database connection settings.
     */
    public function getPrefix() : string
    {
        // get the current prefix
        $prefix = $this->prefix;

        // get the prefix
        if ($this->prefix !== '') $prefix = $this->prefix;
        
        // no prefix allowed ? return an empty string
        if ($this->noPrefix) $prefix = '';

        // return string
        return $prefix;
    }

    /**
     * @method TablePrefixing noPrefix
     * @return void
     */
    public function noPrefix() : void
    {
        $this->noPrefix = true;
    }

    /**
     * @method TablePrefixing resetPrefix
     * @return void
     * 
     * This method resets a prefix
     */
    public function resetPrefix() : void
    {
        $this->noPrefix = false;
        $this->prefix = '';
    }

    /**
     * @method TablePrefixing registerPrefix
     * @param mixed ...$prefixes (multiple)
     * @return void
     *
     * This method registers one or more prefixes
     */
    public static function registerPrefix(...$prefixes) : void
    {
        // check $prefixes count
        if (count($prefixes) > 0) :
        
            foreach ($prefixes as $prefix) :

                // register prefix
                self::$prefixRegistered[] = $prefix;
                
            endforeach;

        endif;
    }

    /**
     * @method TablePrefixing prefixQuery
     * @param string $prefix
     * @param closure $callback
     * @return void
     *
     * This method registers a prefix query
     */
    public static function prefixQuery(string $prefix, closure $callback) : void
    {
        // register a callback for prefix
        self::$prefixRegistry[$prefix][] = $callback;
    }

    /**
     * @method TablePrefixing callPrefixQuery
     * @param $instance
     * @return void
     *
     * Call prefix callbacks
     */
    private function callPrefixQuery(&$instance) : void
    {
        $prefixRegistry = self::$prefixRegistry;

        // get prefix and check in table name
        foreach ($prefixRegistry as $prefix => $arrayOfClosure) :
        
            // quote prefix
            $quote = preg_quote($prefix);

            if (preg_match("/^($quote)/i", $this->table) || preg_match("/(\s+|`)($quote)/", $this->query)) :

                foreach ($arrayOfClosure as $callback) :

                    // call $callback
                    call_user_func_array($callback, [&$instance, &$instance->table, &$instance->query]);

                endforeach;

                break;

            endif;

        endforeach;
    }
}