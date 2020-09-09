<?php
namespace Lightroom\Router\Interfaces;

/**
 * @package GuardInterface
 * @author Amadi Ifeanyi <amadiify.com>
 * @method setIncomingUrl(array $request)
 * @method getIncomingUrl()
 */
interface GuardInterface
{
    /**
     * @method GuardInterface guardInit
     * @return void
     * 
     * This method would be called when guard has been initialized. 
     */
    public function guardInit() : void;
}