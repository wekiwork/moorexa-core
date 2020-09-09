<?php
namespace Lightroom\Core\Interfaces;

/**
 * @package DependencyManagerInterface 
 * @author amadi ifeanyi
 */
interface DependencyManagerInterface
{
    /**
     * @method DependencyManagerInterface callException
     * @param string $child
     * @param string $class
     * @param string $dependencyError
     * @return void
     * 
     * This method would be triggered when a dependency error occurs.
     */
    public function callException(string $child, string $class, string $dependencyError);
}