<?php
namespace Lightroom\Exceptions;

use Exception;

/**
 * @package Interface Not Found exception class
 * @author Amadi Ifeanyi <amadiify.com>
 */
class InterfaceNotFound extends Exception
{
    public function __construct(string $class, string $interface)
    {
        $this->message = 'Sorry! It appears that class "'.$class.'" does not implement "'.$interface.'". This is a required action. Please implement this interface and try again.';
    }
}