<?php
namespace Lightroom\Database\Interfaces;
/**
 * @package ChannelInterface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface ChannelInterface
{
    /**
     * @method ChannelInterface getTable
     * @return string
     */
    public function getTable() : string;

    /**
     * @method ChannelInterface getBind
     * @return array
     */
    public function getBind() : array;

    /**
     * @method ChannelInterface setBind
     * @param array $bind
     * @return void
     */
    public function setBind(array $bind) : void;

    /**
     * @method ChannelInterface getQuery
     * @return string
     */
    public function getQuery() : string;

    /**
     * @method ChannelInterface setQuery
     * @param string $query
     * @return void
     */
    public function setQuery(string $query) : void;

    /**
     * @method ChannelInterface getMethod
     * @return string 
     */
    public function getMethod() : string;

    /**
     * @method ChannelInterface getOrigin
     * @return string 
     */
    public function getOrigin() : string;

    /**
     * @method ChannelInterface getBuilder
     * @return QueryBuilderInterface 
     */
    public function getBuilder() : QueryBuilderInterface;

    /**
     * @method ChannelInterface setBuilder
     * @param QueryBuilderInterface $builder
     * @return void
     */
    public function setBuilder(QueryBuilderInterface $builder) : void;

    /**
     * @method ChannelInterface loadInstance
     * @param string $driver
     * @param array $properties
     * @return ChannelInterfaces
     */
    public static function loadInstance(string $driver, array $properties) : ChannelInterface;
}