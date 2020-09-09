<?php
namespace Lightroom\Adapter;

use Closure;
use Lightroom\Common\{
    Interfaces\LogbookLoggerInterface, Interfaces\ExceptionHandlerInterface, 
    Logbook, ExceptionHandler, Interfaces\ExceptionWrapperInterface
};
use Lightroom\Exceptions\{ClassNotFound,
    ExceptionHandler as CoreExceptionHandler,
    InterfaceNotFound,
    ThrowableExceptions};


trait ProgramFaults
{
    use ExceptionHandler;

    /**
     * @method ProgramFaults default
     * set default logger for application
     * @param string $logger
     * @return ProgramFaults
     */
    public function default(string $logger)
    {
        //set default logger
        Logbook::setDefaultLogger($logger);   

        return $this;
    }

    /**
     * @method ProgramFaults program fault group
     * accepts a closure function, returns class instance
     * @param Closure $closureGroup
     * @return ProgramFaults
     */
    private function programFaultGroup(\closure $closureGroup)
    {
        call_user_func($closureGroup->bindTo($this, static::class));
        
        // use default exception manager
        $this->useDefaultExceptionManager();

        return $this;
    }

    /**
     * @method ProgramFaults logger handler
     * @param array $arrayOfLoggers
     * @param LogbookLoggerInterface $logger
     */
    private function loggerHandlers(array $arrayOfLoggers, LogbookLoggerInterface $logger)
    {
        // load default logger
        Logbook::loggerList($arrayOfLoggers);
    }

    /**
     * @method ProgramFaults exception handler
     * @param ExceptionHandlerInterface $handler
     */
    private function exceptionHandler(ExceptionHandlerInterface $handler)
    {   
        // save handler
        CoreExceptionHandler::setHandler($handler);

        // register as a throwable event
        ThrowableExceptions::setThrowableEvent(CoreExceptionHandler::class);
    }

    /**
     * @method ProgramFaults globalWarnings
     * @param array $warningTypes ['function' => [], 'class' => [], 'variable' => []]
     * Registers a global warning that can be listened by the exception handlers
     * @return void
     * @throws ClassNotFound
     */
    private function globalWarnings(array $warningTypes)
    {
        // using file dependency checker
        $file = ClassManager::singleton(FileDependencyChecker::class);

        // set path to global
        $file->path(FileDependencyChecker::$globalKeyword)->dependency($warningTypes);
    }

    /**
     * @method ProgramFaults silentListener
     * @param string $className
     * @return void
     *
     * This is a quick abstraction method for the SilentErrorListener class
     * Class must implement SilentErrorListenerInterface
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     */
    private function silentListener(string $className)
    {
        // register channel
        \Lightroom\Adapter\Errors\SilentErrorListener::registerChannel($className);
    }
}
