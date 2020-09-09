<?php
namespace Lightroom\Adapter\Errors;
/**
 * @package Class Not Available container
 * @author Amadi Ifeanyi <amadiify.com>
 * A simple container for unavaliable classes
 */
class ClassNotAvaliable
{
    // load all the magic methods
    public function __construct()
    {

    }

    public function __get(string $name)
    {

    }

    public function __set(string $name, $value)
    {

    }

    public function __call(string $method, array $data)
    {
        // return
        return $this;
    }

    public static function __callStatic(string $method, array $data)
    {

    }

    public function __toString()
    {
        return 'Class not avaliable';
    }
}