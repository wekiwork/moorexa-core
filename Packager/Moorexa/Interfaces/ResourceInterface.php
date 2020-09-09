<?php
namespace Lightroom\Packager\Moorexa\Interfaces;
use Lightroom\Packager\Moorexa\RouterMethods;
/**
 * @package ResourceInterface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface ResourceInterface
{
    /**
     * @method ResourceInterface onRequest
     * @param RouterMethods $method
     * @return void
     * 
     * Here is a basic example of how this works.
     * $method->get('hello/{name}', 'methodName');
     * 
     * Where "methodName" is a public method within class.
     * Hope it's simple enough?
     */
    public function onRequest(RouterMethods $method) : void;
}