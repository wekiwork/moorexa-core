<?php
namespace Lightroom\Exceptions;

use Exception;

/**
 * @package File not found exception
 * @author Amadi Ifeanyi <amadiify.com>
 */
class FileNotFound extends Exception
{
    public function __construct(string $filepath)
    {
        $this->message = 'Sorry, we could not load this file "'.$filepath.'" for you, and we are sure this file doesn\'t exist yet. Please can you check and try again?';
    }
}