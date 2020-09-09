<?php
namespace Lightroom\Router\Interfaces;

/**
 * @package Guards RouteGuardInterface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface RouteGuardInterface
{
    /**
     * @method RouteGuardInterface setIncomingUrl
     * @param array $incomingUrl
     * @return void
     */
    public function setIncomingUrl(array $incomingUrl) : void;

    /**
     * @method RouteGuardInterface getIncomingUrl
     * @return array
     */
    public function getIncomingUrl() : array;

    /**
     * @method RouteGuardInterface getRedirectPath 
     * @return string
     * 
     * This method returns a redirect path
     **/
    public function getRedirectPath() : string;
}