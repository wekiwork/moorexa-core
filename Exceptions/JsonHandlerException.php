<?php
namespace Lightroom\Exceptions;

use Exception;

/**
 * @package JsonHandlerException class
 * @author Amadi Ifeanyi <amadiify.com>
 */
class JsonHandlerException extends Exception
{
    public function __construct(string $message)
    {
        $this->message = $message;
    }
}