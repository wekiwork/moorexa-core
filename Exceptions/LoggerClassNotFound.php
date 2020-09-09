<?php
namespace Lightroom\Exceptions;

use Exception;

/**
 * @package logger class not found exception
 * @author Amadi Ifeanyi <amadiify.com>
 */
class LoggerClassNotFound extends Exception
{
    public function __construct(string $classname)
    {
        
    }
}