<?php
namespace Lightroom\Exceptions;

use Lightroom\Core\{Interfaces\DependencyManagerInterface, DependencyFailedManager};
use Exception;

/**
 * @package Dependency failed for class exception
 * @author Amadi Ifeanyi <amadiify.com>
 */
class DependencyFailedForClass extends Exception implements DependencyManagerInterface
{
    use DependencyFailedManager;
    
     /**
     * @method DependencyManagerInterface callException
     * @param string $child
     * @param string $class
     * @param string $dependencyError
     * @return void
     * 
     * This method would be triggered when a dependency error occurs.
     */
    public function callException(string $child, string $class, string $dependencyError)
    {
        $this->message = 'Dependency check failed for "'.$child.'", all we know for now is "'.$class.'" requires this class, trait or interface "'.$dependencyError.'"';
    }
}