<?php
namespace Lightroom\Core;

use closure;
use Lightroom\Common\IncludeInjector;

/**
 * @package FrameworkConfiguration
 * @author fregatelab <fregatelab.com>
 */
class FrameworkConfiguration
{
    use IncludeInjector;

    /**
     * @method FrameworkConfiguration __construct
     * @param closure $configurationClosure
     */
    public function __construct(closure $configurationClosure)
    {
        // call closure function
        call_user_func($configurationClosure->bindTo($this, static::class));

        // clean up
        unset($configurationClosure);
    }
}