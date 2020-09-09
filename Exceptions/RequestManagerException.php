<?php
namespace Lightroom\Exceptions;

use Exception;

/**
 * @package Request Manager Exceptions
 * @author fregatelab <fregatelab.com<
 */
class RequestManagerException extends Exception
{
    /**
     * @method RequestManagerException __construct
     */
    public function __construct()
    {
        $this->message = 'It appears that you don\'t have a default request manager. Please register one with the payload class and try again.';
    }
}