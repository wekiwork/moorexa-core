<?php
namespace Lightroom\Adapter\Errors;

use Lightroom\Core\GlobalConstants;

class FrameworkDefault extends \Exception
{
    // @var bool fromExceptionHandler
    public static $fromExceptionHandler = false;

    /**
     * @method FrameworkDefault __construct
     * @param \Exception $exception
     */
    public function __construct($exception)
    {
        
        // clean output
        (strlen(ob_get_contents()) > 0) ? ob_clean() : null;
        
        // build vars
        $var = $this->buildVar($exception);

        // get trace back
        $traceBack = $this->getTraceBack($var);

        // error body
        $body = $this->getErrorBody($var, $traceBack);

        // can debug
        $debug = function_exists('env') ? env('bootstrap', 'debug_mode') : true;

        // set status
        http_response_code(500);

        // run debug
        $debug = is_null($debug) ? true : $debug;

        // can we debug?
        if ($debug) :

            if (!defined('TEST_ENVIRONMENT_ENABLED')) :

                // show error
                echo $this->outputErrorToScreen($body);

                // kill processes
                die();

            else:

                echo $exception->getMessage();

            endif;
            

        else:

            // get logger
            $logger = logger('monolog');

            // log error    
            if ($logger !== null) $logger->error($exception->getMessage(), ['file' => $exception->getFile(), 'Line' => $exception->getLine()]);

        endif;
    }

    /**
     * @method FrameworkDefault build vars
     */
    public function buildVar($exception) : array
    {
        $var = [];

        if (self::$fromExceptionHandler) :
        
            $var = [
                'code' => $exception->getCode(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'str'  => $exception->getMessage(),
                'trace' => $exception->getTrace(),
                'className' => get_class($exception)
            ];
        
        else:
        
            $var = [
                'code' => $this->getCode(),
                'line' => $this->getLine(),
                'file' => $this->getFile(),
                'str'  => $exception,
                'trace' => $this->getTrace(),
                'className' => get_class($this)
            ];

        endif;

        return $var;
    }

    /**
     * @method FrameworkDefault get trace back
     */
    public function getTraceBack(array $var) : string
    {
        $traceBack = '';

        if (isset($var['trace'][0]['file'])) :
        
            $tf = $var['trace'][0]['file'];

            if (isset($var['trace'][0]['line'])) :
            
                $traceBack = '<h3> Trace </h3>
                    <div>
                        <code>File: '.$tf.'</code>
                    </div>
                    <div>
                        <code>Line: '.$var['trace'][0]['line'].'</code>
                    </div>
                ';

            endif;

            // clean up
            unset($tf, $var);
    
        endif;

        return $traceBack;
    }

    /**
     * @method FrameworkDefault get error body
     * @param array $var
     * @param string $traceBack
     * @return string
     */
    public function getErrorBody(array $var, string $traceBack) : string
    {
        $file = $var['file'];

        $var['file'] = '<div>
            <code>File: '.$var['file'].'</code>
        </div>';

        $var['line'] = '<div>
            <code>line: '.$var['line'].'</code>
        </div>';

        return '<div class="error-list-body" style="margin-bottom: 30px; padding: 15px; border-bottom: 1px solid #eee;">
        <h1 style="font-size: 25px; font-weight: normal;">'.ucfirst(basename($file)).'</h1>
        <div class="error-list-body-message">
            <code style="display: block; padding: 10px; margin-bottom: 10px; ">
            '.$var['str'].'</code>
        </div>
        '.$var['file'].$var['line'].'
        <br>
		'.$traceBack.'
        </div>';
    }

    // get working directory
    private function workingDir()
    {
        // get directory
        $directory = __DIR__;

        // get document root
        $root = $_SERVER['DOCUMENT_ROOT'];

        // remove root from directory
        $directory = substr($directory, strlen($root));

        // clean up
        unset($root);

        return $directory;
    }

    private function outputErrorToScreen($error)
    {
        $button = '<a href="" class="btn mor-btn mor-reload">Check Again</a>';

        $has_suggestion = "please contact <a href=\"mailto:support@moorexa.com\"> support@moorexa.com </a> for a quick guide";

        $title = 'Opps! Something went wrong.';

        $developer = 'Moorexa PHP Engineer';

        $moorexaCss = $this->workingDir() . '/css/moorexa.css';
        $wrapperCss = $this->workingDir() . '/css/wrapper.css';

        return <<< EOD
        <!doctype html>
        <html lang="en">
        <head>
        <title>Oops! This is an exception</title>
        <style type="text/css">body{background:#0a0b15;}</style>
        <link rel="stylesheet" type="text/css" href="{$wrapperCss}"/>
        <link rel="stylesheet" type="text/css" href="{$moorexaCss}"/>
        </head>
        <body>
        <div class="mor-error-box wrapper"
        style="width: auto; max-width: 100%;"><div class=w1-end><div class=wrapper><h1
        class="error-box-title w1-end">{$title}</h1><div class=w1-13 style="padding: 0;"><div
        class=statement-box>{$error} </div></div><div class="w13-end error-box-sidebar"
        style="margin-left:2px"><div class=error-box-suggestion><h1>This may help</h1><p>Hi {$developer},
        {$has_suggestion}. You could also try other possible means, like speak to <a
        href="mailto:helloamadiify@gmail.com" style="text-decoration:none">@amadiify</a>, he could be of help
        to you or maybe copy the error you see and ask other moorexa developers on <a
        href="https://stackoverflow.com/questions/tagged/moorexa" target=_blank>Stack Overflow</a>. Thank
        you for building with Moorexa. </p></div><div class="error-box-action wrapper"><h1
        class=w1-end>Available Options</h1><div class=w1-17>{$button} <a href="javascript:history.back()"
        class="btn mor-btn mor-error">Dismiss</a></div></div></div></div></div></div>
        </body>
        </html>
EOD;

    }
}   