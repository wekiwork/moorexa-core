<?php
namespace Lightroom\Router\Interfaces;

use Closure;
/**
 * @package MiddlewareInterface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface MiddlewareInterface
{
    /**
     * @method MiddlewareInterface request
     * @param Closure $render
     * @return void
     * 
     * This method holds the waiting request, call render to push view to browser.
     **/
    public function request(Closure $render) : void;

    /**
     * @method MiddlewareInterface requestClosed
     * @return void
     * 
     * This method would be called when request has been closed.
     **/
    public function requestClosed() : void;
}