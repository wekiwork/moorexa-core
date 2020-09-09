<?php
namespace Lightroom\Events;

/**
 * @package Basic Event Class
 * @author Amadi Ifeanyi <amadiify.com>
 */
class ExampleBasic
{
    /**
     * @method ExampleBasic hello
     * @return void
     * 
     * This is a simple implementation of a typical event method.
     * Now, you can call Listener::<placeholder>('hello', function($message){}); or
     * Dispatcher::<placeholder>('hello', 'Hello my friend');
     */
    public static function hello(string $message) : void 
    {
        // you can also call another event method from here.
    }
}