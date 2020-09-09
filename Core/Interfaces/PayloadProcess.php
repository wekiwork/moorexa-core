<?php
namespace Lightroom\Core\Interfaces;

use Closure;

/**
 * @package PayloadProcess interface
 * @author fregatelab <fregatelab.com>
 * @author Amadi ifeanyi <amadiify.com>
 * 
 * This interface helps facilitate process request.
 */
interface PayloadProcess
{
    /**
     * @method processComplete
     * This method would be called when process for class has been complete
     * @var closure $next
     */
    public function processComplete(Closure $next);
}