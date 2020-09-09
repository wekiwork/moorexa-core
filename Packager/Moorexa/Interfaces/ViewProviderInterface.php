<?php
namespace Lightroom\Packager\Moorexa\Interfaces;

use Closure;
/**
 * @package ViewProvider Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface ViewProviderInterface
{
    /**
     * @method ViewProviderInterface setArguments
     * @param array $arguments
     * 
     * This method sets the view arguments
     */
    public function setArguments(array $arguments) : void;

    /**
     * @method ViewProviderInterface viewWillEnter
     * @param Closure $next
     * 
     * This method would be called before rendering view
     */
    public function viewWillEnter(Closure $next) : void;
}