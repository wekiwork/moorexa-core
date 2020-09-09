<?php

namespace Lightroom\Core\Interfaces;

/**
 * @package Constant interface 
 * @author Fregatelab <fregatelab.com>
 */

interface ConstantInterface
{
    // @method ConstantInterface getName
    // get constant name
    public function getName() : string;

    // @method ConstantInterface getValue
    // Get constant value
    public function getValue() : string;
}
