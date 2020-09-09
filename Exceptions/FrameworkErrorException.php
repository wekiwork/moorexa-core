<?php
namespace Lightroom\Exceptions;

/**
 * @package framework error handler exception class
 * @author amadi ifeanyi <amadiify.com>
 */
class FrameworkErrorException extends \Exception
{
    public function __construct($num, $str, $file, $line)
    {
        // set message
        $this->message = $str;

        // set file
        $this->file = $file;

        // set line
        $this->line = $line;

        // set code
        $this->code = $num;
    }
}
