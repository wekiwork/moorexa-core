<?php
namespace Lightroom\Queues;

use Closure;
use Opis\Closure\SerializableClosure;
/**
 * @package QueueContainer
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is a basic queue container
 */
class QueueContainer
{
    /**
     * @var string $jobName
     * The name of the job you are running
     */
    private $jobName = '';

    /**
     * @var Closure $job 
     * This is the job to execute
     */
    private $job;

    /**
     * @var array $closureScope
     */
    private $closureScope = [];

    /**
     * @method QueueContainer setJobName
     * @param string $jobName
     */
    public function setJobName(string $jobName) : void 
    {
        $this->jobName = $jobName;
    }

    /**
     * @method QueueContainer setJob
     * @param Closure $jobClass
     * @param string $jobMethod
     * @param array $args 
     */
    public function setJob(Closure $closureFunction)
    {
        // load reflection
        $reflection = new \ReflectionFunction($closureFunction);

        // clean file name
        $fileName = str_replace(getcwd() . '/', '', $reflection->getFileName());
        
        // set scope
        $this->closureScope = ['namespace' => $reflection->getClosureScopeClass()->name, 'file' => $fileName];

        // set job
        $this->job = serialize(new SerializableClosure($closureFunction));
    }

    /**
     * @method QueueContainer getClosureScope
     * @return array 
     */
    public function getClosureScope() : array 
    {
        return $this->closureScope;
    }
    
    /**
     * @method QueueContainer getJobName
     * @return string
     */
    public function getJobName() : string
    {
        return $this->jobName;
    }

    /**
     * @method QueueContainer getJob
     * @return string
     */
    public function getJob() : string 
    {
        return $this->job;
    }
    
}