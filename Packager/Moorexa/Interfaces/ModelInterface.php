<?php
namespace Lightroom\Packager\Moorexa\Interfaces;

use Closure;
use Lightroom\Packager\Moorexa\MVC\Helpers\ControllerLoader;
/**
 * @package Moorexa Model Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface ModelInterface
{
    /**
     * @method ModelInterface loadRequestMethodForView
     * @param ControllerLoader $loader
     * @param ModelInterface $model
     * @return void
     */
    public function loadRequestMethodForView(ControllerLoader $loader, ModelInterface $model) : void;

    /**
     * @method ModelInterface onModelInit
     * @param ModelInterface $model
     * @param Closure $next
     * @return void
     */
    public function onModelInit(ModelInterface $model, Closure $next) : void;

    /**
     * @method ModelInterface get
     * @param string $property
     * @return mixed
     * 
     * This method returns the value of a model property
     */
    public function get(string $property);

    /**
     * @method ModelInterface set
     * @param string $property
     * @param mixed $value
     * @return void
     * 
     * This method sets the value of a model property
     */
    public function set(string $property, $value) : void;
}