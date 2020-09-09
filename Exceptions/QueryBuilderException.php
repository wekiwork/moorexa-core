<?php
namespace Lightroom\Exceptions;

use Exception;

/**
 * @package Query Builder Exceptions
 * @author fregatelab <fregatelab.com<
 */
class QueryBuilderException extends Exception
{
    /**
     * @method QueryBuilderException __construct
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->message = 'Ops! You have an error from the query builder. It appears that, ' . $message;
    }
}