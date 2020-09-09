<?php
namespace Lightroom\Common\Interfaces;

/**
 * @package Requirement interface
 * @author fregatlab <fregatelab.com>
 * @author Amadi ifeanyi <amadiify.com>
 */

interface RequirementInterface
{
    /**
     * @method RequirementInterface loadAll requirements
     */
    public function loadAll() : array;

    /**
     * @method RequirementInterface requirementFailed
     * @param array $failed
     */
    public function requirementFailed(array $failed);

    /**
     * @method RequirementInterface setMethod
     * set requirement error
     * @param string $requirement
     * @param string $error
     */
    public function setError(string $requirement, string $error);

    /**
     * @method RequirementInterface getClass
     * get requirement error
     * @param string $requirement
     * @return string
     */
    public function getError(string $requirement) : string;
}