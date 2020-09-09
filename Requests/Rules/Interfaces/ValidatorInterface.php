<?php
namespace Lightroom\Requests\Rules\Interfaces;

/**
 * @package Validator Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface ValidatorInterface
{
    /**
     * @method ValidatorInterface loadData
     * @param mixed $data 
     * @return void
     */
    public function loadData($data) : void;


    /**
     * @method ValidatorInterface validate
     * @param array $rules
     * @param array $errors
     * @param array $post
     */
    public function validate(array $rules, array &$errors = [], array &$post = []);
}