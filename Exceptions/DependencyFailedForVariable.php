<?php
namespace Lightroom\Exceptions;

use Lightroom\Core\{
    Interfaces\DependencyManagerInterface, DependencyFailedManager
};
use Exception;

/**
 * @package Dependency failed for variable exception
 * @author Amadi Ifeanyi <amadiify.com>
 */
class DependencyFailedForVariable extends Exception implements DependencyManagerInterface
{
    use DependencyFailedManager;
    
     /**
     * @method DependencyManagerInterface callException
     * @param string $child
     * @param string $file
     * @param string $dependencyError
     * @return void
     * 
     * This method would be triggered when a dependency error occurs.
     */
    public function callException(string $child, string $file, string $dependencyError)
    {
        $this->message = 'Dependency check failed for "'.$child.'", all we know for now is "'.$file.'" requires this variable "'.$dependencyError.'"';
    }
}