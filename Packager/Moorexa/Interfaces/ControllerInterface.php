<?php
namespace Lightroom\Packager\Moorexa\Interfaces;

/**
 * @package ControllerInterface
 * @author Amadi Ifeanyi <amadiify.com>
 * @method ___getViewProvider()
 */
interface ControllerInterface
{
    /**
     * @method ControllerInterface initController
     * @param ControllerInterface $controller
     * @return void
     */
    public function initController(ControllerInterface $controller) : void;

    /**
     * @method ControllerInterface setActiveViewProvider
     * @param ViewProviderInterface $provider
     * @return void
     */
    public function setActiveViewProvider(ViewProviderInterface $provider) : void;

    /**
     * @method ControllerInterface getActiveViewProvider
     * @return ViewProviderInterface
     */
    public function getActiveViewProvider() : ViewProviderInterface;

    /**
     * @method ControllerInterface setActiveControllerProvider
     * @param ControllerProviderInterface $provider
     * @return void
     */
    public function setActiveControllerProvider(ControllerProviderInterface $provider) : void;

    /**
     * @method ControllerInterface getActiveControllerProvider
     * @return ControllerProviderInterface
     */
    public function getActiveControllerProvider() : ControllerProviderInterface;

    /**
     * @method ControllerInterface setActiveViewModel
     * @param ModelInterface $model
     * @return void
     */
    public function setActiveViewModel(ModelInterface $model) : void;

    /**
     * @method ControllerInterface loadControllerVariables
     * @return void
     */
    public function loadControllerVariables() : void;
}