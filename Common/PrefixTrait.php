<?php
namespace Lightroom\Common;

trait PrefixTrait
{
    /**
     * ********
     * Prefix created
     * @var PrefixTrait $prefixCreated
     */
    private static $prefixCreated = [];

    /**
     * @method createPrefixRegister
     * Create a new prefix and save to @var $prefixCreated
     * @var PrefixTrait $prefix
     * @var PrefixTrait $mixed
     */
    public static function createPrefixRegister(string $prefix, $mixed)
    {
        // save prefix
        static::$prefixCreated[$prefix] = $mixed;
    }

    /**
     * @method getPrefixFromRegister
     * Gets prefix saved in
     * @param string $prefix
     * @return mixed
     */
    public static function getPrefixFromRegister(string $prefix)
    {
        // return data
        $returnData = null;

        // get prefix
        if (isset(static::$prefixCreated[$prefix])) :
        
            $returnData = static::$prefixCreated[$prefix];
            
        endif;

        return $returnData;
    }
}