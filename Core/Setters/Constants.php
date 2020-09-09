<?php
namespace Lightroom\Core\Setters;

use Lightroom\Core\Interfaces\ConstantInterface;

class Constants implements ConstantInterface
{
    /**
     * @var Constants $constant_name
     */
    private $constant_name;

    /**
     * @var Constants $constant_value
     */
    private $constant_value;

    /**
     * @var Constants $constant_dump
     */
    private $constant_dump = [];

    /**
     * @var Constants $constant_index
     */
    public $constant_index = 0;

    /**
     * @method Constants getName
     * Returns the current class constant name
     */
    public function getName() : string
    {
        return $this->constant_dump[(int) $this->constant_index]['name'];
    }

    /**
     * @method Constants getValue
     * Returns the current class constant value
     */
    public function getValue() : string
    {
        // get current index
        $current = $this->constant_index;

        // increment index
        $this->constant_index++;

        // return value
        return $this->constant_dump[(int) $current]['value'];
    }

    /**
     * @method Constants name
     * Set the current constant name
     * @param string $name
     * @return Constants
     */
    public function name(string $name) : Constants
    {
        $this->constant_name = $name;

        // return instance
        return $this;
    }

    /**
     * @method Constants value
     * Set the current constant value
     * @param string $value
     * @return Constants
     */
    public function value(string $value) : Constants
    {
        $this->constant_value = $value;

        // save now
        $this->constant_dump[] = ['name' => $this->constant_name, 'value' => $value];
        
        // return instance
        return $this;
    }
}