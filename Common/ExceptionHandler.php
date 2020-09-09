<?php
namespace Lightroom\Common;

use Lightroom\Common\Interfaces\{
    ExceptionHandlerInterface, ExceptionWrapperInterface
};
use Lightroom\Adapter\Errors\FrameworkDefault;
use Lightroom\Adapter\ProgramFaults;
use Lightroom\Exceptions\FrameworkErrorException;
/**
 * @package exception handler required methods
 * @author amadi ifeanyi <amadiify.com>
 */
trait ExceptionHandler
{
    // @var exception class
    private $exceptionClass = '';

    // @var exception class method
    private $exceptionClassMethod = '';

    // @var exception instance
    private $exceptionInstance = null;

    // @var bool $defaultExceptionHandlerRegistered
    private static $defaultExceptionHandlerRegistered = false;

    /**
     * @method ExceptionHandlerInterface exception class name
     * @param string $className
     * @return ExceptionHandlerInterface
     */
    public function exceptionClass(string $className) : ExceptionHandlerInterface
    {
        $this->exceptionClass = $className;

        return $this;
    }

    /**
     * @method ExceptionHandlerInterface exception class method
     * @param string $method
     * @return ExceptionHandlerInterface
     */
    public function exceptionMethod(string $method) : ExceptionHandlerInterface
    {
        $this->exceptionClassMethod = $method;
        
        return $this;
    }

    /**
     * @method ExceptionHandlerInterface get exception class name
     */
    public function getExceptionClass() : string
    {
        return $this->exceptionClass;
    }

    /**
     * @method ExceptionHandlerInterface get exception class method
     */
    public function getExceptionMethod() : string
    {
        return $this->exceptionClassMethod;
    }

    /**
     * @method ExceptionHandlerInterface silent ExceptionHandler
     */
    public function silent()
    {
        // register exception handler to an empty closure function
        set_exception_handler(function(){});

        // register error handler to an empty closure function
        set_error_handler(function(){});
    }

    /**
     * @method ProgramFaults useDefaultExceptionManager
     * use default exception manager if no exception handler was registered
     */
    private function useDefaultExceptionManager()
    {
        if ($this->exceptionInstance == null && self::$defaultExceptionHandlerRegistered === false) :
        
            set_error_handler(function($num, $str, $file, $line)
            {
                if (strpos($str, 'headers already sent') !== false) :

                    // get content
                    $content = ob_get_contents();

                    // clear header
                    strlen($content) > 0 ? ob_clean() : null;

                    // start header
                    \ob_start();
                    
                else:
                    if ($str != '') $this->displayerrors((new FrameworkErrorException($num, $str, $file, $line)));
                endif;
            });

            // set exception handler
            set_exception_handler(function($exception)
            {
                $this->displayerrors($exception);
            });

            // default handler registered
            self::$defaultExceptionHandlerRegistered = true;

        endif;
    }

    /**
     * @method ProgramFaults displayerrors
     * @param $exception
     * @throws FrameworkDefault
     */
    private function displayerrors($exception)
    {
        // raise flag
        FrameworkDefault::$fromExceptionHandler = true;

        // get internal display error settings
        $displayError = ini_get('display_errors');

        if (strtoupper($displayError) == 'ON') :
            
            throw new FrameworkDefault($exception);

        endif;

        // clean up
        unset($exception, $displayError);
    }

    /**
     * @method ProgramFaults saveHandlerInstance
     * @param ExceptionWrapperInterface $handler
     */
    public function saveHandlerInstance(ExceptionWrapperInterface $handler) : void
    {
        $this->exceptionInstance = $handler;

        // clean up
        unset($handler);
    }

    /**
     * @method ProgramFaults getHandlerInstance
     */
    public function getHandlerInstance() : ExceptionWrapperInterface
    {
        return $this->exceptionInstance;
    }
}