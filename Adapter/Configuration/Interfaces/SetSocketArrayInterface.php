<?php
namespace Lightroom\Adapter\Configuration\Interfaces;

/**
 * @package set socket array interface
 */
interface SetSocketArrayInterface
{
    public function setSocketClass(string $class) : SetSocketArrayInterface;
    public function setSocketMethod(string $method) : SetSocketArrayInterface;
    public function getSocketClass() : string;
    public function getSocketMethod() : string;
}