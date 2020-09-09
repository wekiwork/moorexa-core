<?php
namespace Lightroom\Packager\Moorexa;

use Exception;
use Lightroom\Adapter\Interfaces\SilentErrorListenerInterface;

/**
 * @package MoorexaSilentErrorListener
 * @author Amadi Ifeanyi <amadiify.com>
 */
class MoorexaSilentErrorListener implements SilentErrorListenerInterface
{
    /**
     * @method SilentErrorListenerInterface exceptionOccured
     * @param Exception $exception
     */
    public function exceptionOccured($exception) : void
    {
        // get the message 
        $message = $exception->getMessage();

        // get the class that threw this exception
        $class = get_class($exception);

        // add to error
        logger()->error($message, [
            'class' => $class, 
            'line' => $exception->getLine(),
            'file' => $exception->getFile(),
            'code' => $exception->getCode()
        ]);
    }
}