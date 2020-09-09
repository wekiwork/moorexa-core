<?php
namespace Lightroom\Exceptions\Interfaces;

use Exception;

/**
 * @package ThrowableExceptionInterface for ThrowableException class
 * @author amadi ifeanyi <amadiify.com>
 * 
 * This interface provides a method that listens for ThrowableException event
 */
interface ThrowableExceptionInterface
{
    /**
     * @method ThrowableExceptionInterface throwableFired
     * @param Exception $exception reference
     * @return null
     */
    public function throwableFired(&$exception);
}