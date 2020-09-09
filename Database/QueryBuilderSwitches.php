<?php
namespace Lightroom\Database;

/**
 * @package Query Builder Switches
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This creates a default handler for __call and __callStatic magic methods.
 * Utilizing class shall implements Lightroom\Database\Interfaces\DriverQueryBuilder
 */
trait QueryBuilderSwitches
{
    /**
     * @method QueryBuilderSwitches __call
     * @param string $method
     * @param array $arguments
     *
     * This would handle all undefined methods and channel them to the query builder
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        // get builder
        $builder = $this->getQueryBuilder();

        // reset builder
        if (method_exists($builder, 'resetBuilder')) :

            // reset
            $builder = $builder->resetBuilder();

        endif;

        // return instance
        return call_user_func_array([$builder, $method], $arguments);
    }

    /**
     * @method QueryBuilderSwitches __callStatic
     * @param string $method
     * @param array $arguments
     *
     * This would handle all undefined static methods and channel them to the query builder
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        // get builder
        $builder = self::getDriverStaticInstance()->getQueryBuilder();

        // reset builder
        if (method_exists($builder, 'resetBuilder')) :

            // reset
            $builder = $builder->resetBuilder();

        endif;

        // return instance
        return call_user_func_array([$builder, $method], $arguments);
    }
}