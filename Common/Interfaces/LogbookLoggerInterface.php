<?php
namespace Lightroom\Common\Interfaces;

/**
 * @package logbook logger interface
 * @author Amadi ifeanyi <amadiify.com>
 */
interface LogbookLoggerInterface
{
    /**
     * @method LogbookLoggerInterface default
     * set the default logger
     * @param string $logger
     */
    public function default(string $logger);
}