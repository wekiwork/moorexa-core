<?php
namespace Lightroom\Common;

use closure;
use Lightroom\Core\Interfaces\PayloadProcess;
use Lightroom\Common\Interfaces\RequirementInterface;
use ReflectionException;

/**
 * @package Requirement class
 * @author fregatelab <fregatelab.com>
 */
class Requirements implements PayloadProcess
{
    /**
     * @var bool $requirementsPassed
     */
    private $requirementsPassed = false;


    /**
     * @method Requirements __construct
     * @param RequirementInterface $requirementHandler
     * @throws ReflectionException
     */
    public function __construct(RequirementInterface $requirementHandler)
    {
        $this->loadRequirementList($requirementHandler);
    }

    /**
     * @method Requirements loadRequirementList
     * Load all requirements
     * @throws ReflectionException
     * @var RequirementInterface $requirement
     */
    private function loadRequirementList(RequirementInterface $requirement)
    {
        $requirements = $requirement->loadAll();

        $requirementPassed = 0;
        $requirementFailed = [];
        $requirementClass = [];

        foreach ($requirements as $requirementName => &$requirementArray) :

            list($passed, $instance) = $this->requirementPassed($requirementArray, $requirementClass);

            // if true, we acknowledge that requirement was passed successfully
            if ($passed === true) :
                
                // increment requirement passed by 1
                $requirementPassed++;
            
            else:
            
                // we just save the error returned and pass it to RequirementInterface/requirementFailed() method
                $requirementFailed[$requirementName] = $instance->getError($requirementName);

            endif;

            // clean up
            unset($passed, $instance);

        endforeach;

        // clean up
        unset($requirementName, $requirementArray);

        // check if all requirements was passed successfully.
        if ($requirementPassed < count($requirements)) :
        
            $requirement->requirementFailed($requirementFailed);
        
        else:
        
            $this->requirementsPassed = true;

        endif;
    }

    /**
     * @method Requirements processComplete
     * payload method to push cursor to the next process
     * @param closure $next
     */
    public function processComplete(closure $next)
    {
        if ($this->requirementsPassed) $next();
    }

    /**
     * @method Requirements requirementPassed
     * Returns an array of requirement method instance and return value
     * @param array $requirementArray
     * @param array $requirementClass
     * @return array
     * @throws ReflectionException
     */
    private function requirementPassed(array $requirementArray, array &$requirementClass)
    {
        // get class and method
        list($class, $method) = $requirementArray;

        // get instance
        $instance = isset($requirementClass[$class]) ? $requirementClass[$class] : null;

        // check if called previously
        if (!isset($requirementClass[$class])) :
        
            // create reflection class for $class
            $reflection = new \ReflectionClass($class);

            // get class instance
            $instance = $reflection->newInstanceWithoutConstructor();

            // add 
            $requirementClass[$class] = $instance;

            // clean up
            unset($reflection, $requirementArray, $class);
    
        endif;

        // get return value
        // should be either true or false
        return [$instance->{$method}(), $instance];
    }
}
