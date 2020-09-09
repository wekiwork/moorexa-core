<?php
namespace Lightroom\Exceptions;

use Exception;

/**
 * @package Method not found exception
 * @author Amadi Ifeanyi <amadiify.com>
 */
class MethodNotFound extends Exception
{
    public function __construct(string $classname, string $method)
    {
        $this->message = 'Sorry, we could not load this class method "'.$method.'" from "'.$classname.'". Please ensure method exists and try again.';
    }
}