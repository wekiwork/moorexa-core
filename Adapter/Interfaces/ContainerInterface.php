<?php
namespace Lightroom\Adapter\Interfaces;

use Lightroom\Adapter\Container;
/**
 * @package ContainerInterface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface ContainerInterface
{
    /**
     * @method ContainerInterface registryCalled
     * @param Container $container
     * @return void
     */
    public static function registryCalled(Container $container) : void;

    /**
     * @method ContainerInterface classCalled
     * @param string $className
     * @return void
     */
    public static function classCalled(string $className) : void;

    /**
     * @method ContainerInterface classDropped
     * @param string $className
     * @return void
     */
    public static function classDropped(string $className) : void;
}