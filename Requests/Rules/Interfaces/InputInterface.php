<?php
namespace Lightroom\Requests\Rules\Interfaces;

use Lightroom\Requests\Rules\Interfaces\ValidatorInterface;
/**
 * @package Input Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface InputInterface
{
    /**
     * @method InputInterface getValidator
     * @param array $data
     * @return ValidatorInterface
     */
    public function getValidator(array $data) : ValidatorInterface;

    /**
     * @method InputInterface setValidator
     * @param string $validatorClass
     * @return InputInterface
     */
    public function setValidator(string $validatorClass) : InputInterface;

    /** 
     * @method InputInterface getClassInstance
     * @return mixed
     */
    public function getClassInstance();

    /**
     * @method InputInterface bindTo
     * @param mixed $classInstance
     * @param string $databaseType
     * @return InputInterface
     */
    public function bindTo($classInstance, string $databaseType = '') : InputInterface;
}