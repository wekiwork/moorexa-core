<?php
namespace Lightroom\Exceptions;

use Exception;

/**
 * @package MoorexaInvalidEnvironmentFile exception
 * @author Amadi Ifeanyi <amadiify.com>
 */
class MoorexaInvalidEnvironmentFile extends Exception
{
    public function __construct(string $file)
    {
        $this->message = 'Invalid Environment file "'.$file.'". Must be a valid .yaml file.';
    }
}