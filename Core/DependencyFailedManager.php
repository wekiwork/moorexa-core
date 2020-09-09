<?php
namespace Lightroom\Core;

use Exception;

/**
 * @package Dependency Manager trait
 * @author amadi ifeanyi <amadiify.com>
 * 
 * This must be used with a class that extends Exception class and implement call message method.
 */
trait DependencyFailedManager
{
    public function __construct(string $class, string $child, string $dependencyError, string $errorType = 'file', $exception = null)
    {
        // set the file and line number
        if ($exception === null) :

            // get all traces
            $traces = $this->getTrace();

            // find trace with function spl_autoload_call
            foreach ($traces as $trace) :

                if (isset($trace['function']) && $trace['function'] == 'spl_autoload_call') :

                    // update file
                    $this->file = isset($trace['file']) ? $trace['file'] : __FILE__;

                    // update file line
                    $this->line = isset($trace['line']) ? $trace['line'] : __LINE__;
                    break;

                endif;
            
            endforeach;

            // clean up
            unset($traces, $trace);

        else :

            $this->file = $exception->getFile();
            $this->line = $exception->getLine();

        endif;
        
        // set message
        $this->callException($child, $class, $dependencyError);
    }
}

