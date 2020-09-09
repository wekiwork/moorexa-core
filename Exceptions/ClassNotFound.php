<?php
namespace Lightroom\Exceptions;

use Exception;

/**
 * @package Class not found exception
 * @author Amadi Ifeanyi <amadiify.com>
 */
class ClassNotFound extends Exception
{
    public function __construct(string $classname)
    {
        $this->message = 'Sorry, Moorexa could not load this class "'.$classname.'". And we are sure that, this class doesn\'t exist yet. Please check your file system and try again thanks.';
    }
}