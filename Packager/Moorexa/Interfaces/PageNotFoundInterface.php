<?php
namespace Lightroom\Packager\Moorexa\Interfaces;
/**
 * @package PageNotFoundInterface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface PageNotFoundInterface
{
    /**
     * @method PageNotFoundInterface pageNotFound
     * @param array  $route 
     * @param string $controller 
     * @return bool
     */
    public static function pageNotFound(array $route, string $controller = '') : bool;
}