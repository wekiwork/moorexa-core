<?php
namespace Lightroom\Database\Interfaces;

/**
 * @package Drivers Channel Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface DriverChannelInterface
{
    /**
     * @method DriverChannelInterface ready
     * @param string $method
     * @param ChannelInterface $channel
     * @return void
     * 
     * This method would be called before PHP PDO prepare statement.
     */
    public static function ready(string $method, ChannelInterface $channel) : void;
}