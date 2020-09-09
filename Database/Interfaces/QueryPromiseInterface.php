<?php
namespace Lightroom\Database\Interfaces;

interface QueryPromiseInterface
{
    /**
     * @method QueryPromiseInterface getFetchMethods
     * @return array
     * 
     * Get fetch methods for query builder.
     */
    public static function getFetchMethods() : array;

    /**
     * @method QueryPromiseInterface hasFetchMethod
     * @param string $method
     * @return bool
     */
    public static function hasFetchMethod(string $method) : bool;
}