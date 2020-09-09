<?php
namespace Lightroom\Database\Interfaces;

/**
 * @package Driver Query Builder
 * @author Amadi Ifeanyi
 */
interface DriverQueryBuilder
{
    /**
     * @method DriverQueryBuilder getQueryBuilder
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder() : QueryBuilderInterface;

    /**
     * @method DriverQueryBuilder __call
     * @param string $method
     * @param array $arguments
     * 
     * This would handle all undefined methods and channel them to the query builder
     */
    public function __call(string $method, array $arguments);

    /**
     * @method DriverQueryBuilder __callStatic
     * @param string $method
     * @param array $arguments
     * 
     * This would handle all undefined static methods and channel them to the query builder
     */
    public static function __callStatic(string $method, array $arguments);
}