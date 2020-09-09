<?php
namespace Lightroom\Packager\Moorexa\Helpers;

/**
 * @package Router Properties
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait RouterProperties
{
    private static $requestMethod = null;
    public  static $routesCalled = [];
    private static $closureUsed = [];
    private static $closureName = null;
}