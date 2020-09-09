<?php
namespace Lightroom\Packager\Moorexa\MVC\Helpers;

use closure;
use Lightroom\Core\Payload;
use Lightroom\Router\Middlewares;
use Lightroom\Core\Interfaces\PayloadProcess;

/**
 * @package ControllerMiddlewareLoader
 * @author Amadi Ifeanyi <amadiify.com>
 */
class ControllerMiddlewareLoader implements PayloadProcess
{
    /**
     * @var bool $middlewareLoaded
     */
    private $middlewareLoaded = false;

    /**
     * @method ControllerMiddlewareLoader constructor
     * @param array $incomingUrl
     * @return void
     */
    public function __construct(array $incomingUrl)
    {
        // update bool 
        $this->middlewareLoaded = Middlewares::callLoadedMiddleware($incomingUrl);
    }

    /**
     * @method Payload processComplete
     * payload method to push cursor to the next process
     * @param closure $next
     */
    public function processComplete(closure $next)
    {
        // call the next process
        if ($this->middlewareLoaded) $next();
    }
}