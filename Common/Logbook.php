<?php
namespace Lightroom\Common;

use Lightroom\Exceptions\LoggerClassNotFound;

/**
 * @package Logbook class
 */
class Logbook
{
    // @var null $instance logbook instance
    private static $instance = null;

    // @var default logbook
    private static $defaultLogger = null;

    // @var array logger list
    private static $loggers = [];

    /**
     * @method Logbook set default logger
     * @param string $logger
     */
    public static function setDefaultLogger(string $logger)
    {
        self::$defaultLogger = $logger;
    }

    /**
     * @method Logbook get default logger
     */
    public static function getDefaultLogger()
    {
        return self::$defaultLogger;
    }

    /**
     * @method Logbook get loggers
     */
    public static function getLoggers()
    {
        return self::$loggers;
    }

    /**
     * @method Logbook loggerList
     * receives a list of loggers
     * @param array $loggers
     * @return Logbook
     */
    public static function loggerList(array $loggers) : Logbook
    {
        // get logger instance
        $instance = self::getInstance();

        // push to loggers
        self::$loggers = $loggers;

        // return class instance
        return $instance;
    }

    /**
     * @method Logbook loadDefault
     * load default logger
     * @throws LoggerClassNotFound
     */
    public static function loadDefault()
    {
        // get default logger
        $default = self::getDefaultLogger();

        if ($default !== null)
        
            return self::loadLogger($default);

        return null;
    }

    /**
     * @method Logbook loadLogger
     * @param string $logger
     * @return mixed
     * @throws LoggerClassNotFound
     */
    public static function loadLogger(string $logger)
    {
        static $loggersCalled;
        
        if (is_null($loggersCalled)) :
            
            // create empty array
            $loggersCalled = [];
        
        endif;

        if (isset($loggersCalled[$logger]))

            // return logger from static local variable $loggersCalled;
            return $loggersCalled[$logger];
        

        // get loggers
        $loggers = self::getLoggers();

        // get from logger
        if (isset($loggers[$logger])) : 
        
            // get logger class
            $loggerClass = $loggers[$logger];

            // check if class exists
            if (is_string($loggerClass) && class_exists($loggerClass)) :
            
                $loggersCalled[$loggerClass] = (new $loggerClass());

                return $loggersCalled[$loggerClass];
            
            endif;
            
            
            // throw exception
            throw new LoggerClassNotFound($loggerClass);

        endif;

        // clean up
        unset($loggers, $loggerClass);

        return null;
    }

    /**
     * @method Logbook get logger instance
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) :
        
            self::$instance = new self;
            
        endif;

        return self::$instance;
    }
}