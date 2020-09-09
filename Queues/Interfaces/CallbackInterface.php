<?php
namespace Lightroom\Queues\Interfaces;

use Closure;
/**
 * @package CallbackInterface
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This interface contains some callback methods for job cycles.
 */
interface CallbackInterface
{
    /**
     * @method CallbackInterface taskSent
     * @param string $taskName 
     * @param string $taskBody
     * @return void
     */
    public function taskSent(string $taskName, string $taskBody) : void;

    /**
     * @method CallbackInterface taskReceived
     * @param string $taskName 
     * @param Closure $closureFunction
     * @return void
     */
    public function taskReceived(string $taskName, Closure $closureFunction) : void;

    /**
     * @method CallbackInterface taskComplete
     * @param string $taskName 
     * @param mixed $returnVal
     * @return void
     */
    public function taskComplete(string $taskName, $returnVal) : void;
}