<?php
namespace Lightroom\Router\Functions;
use Lightroom\Router\Guards\RouteGuard;

/**
 * @method RouterHanlder routeGuard
 * @return mixed
 */
function routeGuard()
{
    // create class
    $class = new class(){ use RouteGuard; };

    // return class
    return $class;
}