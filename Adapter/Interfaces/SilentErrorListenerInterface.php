<?php
namespace Lightroom\Adapter\Interfaces;

/**
 * @package SilentError Listener Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface SilentErrorListenerInterface
{
    /**
     * @method SilentErrorListenerInterface exceptionOccured
     * @param Exception $exception
     */
    public function exceptionOccured($exception) : void;
}