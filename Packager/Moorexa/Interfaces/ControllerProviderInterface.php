<?php
namespace Lightroom\Packager\Moorexa\Interfaces;

use Closure;
/**
 * @package ControllerProvider Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface ControllerProviderInterface
{
    /**
     * @method ControllerProviderInterface setArguments
     * @param array $arguments
     * 
     * This method sets the view arguments
     */
    public function setArguments(array $arguments) : void;

    /**
     * @method ControllerProviderInterface boot
     * @param Closure $next
     * 
     * This method would be called before rendering the view
     */
    public function boot(Closure $next) : void;

    /**
     * @method ControllerProviderInterface viewWillEnter
     * @param string $view
     * @param array &$arguments
     * 
     * This method would be called before entering the view
     */
    public function viewWillEnter(string $view, array &$arguments);
}