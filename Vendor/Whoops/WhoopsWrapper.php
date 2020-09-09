<?php
namespace Lightroom\Vendor\Whoops;

use Whoops\Run as Whoops;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\XmlResponseHandler;
use Lightroom\Common\Interfaces\ExceptionWrapperInterface;

/**
 * @package Whoops exception manager wrapper
 */
class WhoopsWrapper implements ExceptionWrapperInterface
{
    /**
     * @var Whoops class instance
     */
    private $instance;

    /**
     * @method WhoopsWrapper constructor
     * Load whoops, and set class instance
     */
    public function __construct()
    {
        if (class_exists(Whoops::class)) $this->instance = new Whoops; 
    }

    /**
     * @method WhoopsWrapper PrettyPageHandler
     * 
     * Shows a pretty error page when something goes pants-up
     */
    public function PrettyPageHandler()
    {
        if ($this->instance === null) return;

        $this->instance->pushHandler(new PrettyPageHandler);
        $this->instance->register();
    }

    /**
     * @method WhoopsWrapper JsonResponseHandler
     * 
     * Captures exceptions and returns information on them as a JSON string. Can be used to, for example, play nice with AJAX requests.
     */
    public function JsonResponseHandler()
    {
        if ($this->instance === null) return;

        $this->instance->pushHandler(new JsonResponseHandler);
        $this->instance->register();
    }

    /**
     * @method WhoopsWrapper PlainTextHandler
     * 
     * Outputs plain text message for use in CLI applications
     */
    public function PlainTextHandler()
    {
        if ($this->instance === null) return;

        $this->instance->pushHandler(new PlainTextHandler);
        $this->instance->register();
    }

    /**
     * @method WhoopsWrapper XmlResponseHandler
     * 
     * Captures exceptions and returns information on them as a XML string. Can be used to, for example, play nice with AJAX requests.
     */
    public function XmlResponseHandler()
    {
        if ($this->instance === null) return;

        $this->instance->pushHandler(new XmlResponseHandler);
        $this->instance->register();
    }
}