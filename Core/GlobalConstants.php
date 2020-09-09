<?php

namespace Lightroom\Core;

use Lightroom\Core\Interfaces\ConstantInterface;

/**
 * @package Global Constant Manager
 * @author Amadi ifeanyi <amadiify.com>
 */

class GlobalConstants
{
    /**
     * @var GlobalConstants $prefix
     */
    private $prefix = '';

    /**
     * @var GlobalConstants $suffix
     */
    private $suffix = '';

    /**
     * @var GlobalConstants $constants
     * A list of constants created
     */
    private $constants = [];

    /**
     * @var GlobalConstants $name
     * Constant name
     */
    public $name;

    /**
     * @var GlobalConstants $value
     * Constant value
     */
    public $value;

    /**
     * @var GlobalConstants $constant
     * The current constant
     */
    private $constant;

    /**
     * @var array $constantCreated
     */
    private static $constantCreated = [];

    /**
     * @method createPrefix
     * Register prefix and return class instance
     * @param string $prefix
     * @return GlobalConstants
     */
    public function createPrefix(string $prefix) : GlobalConstants
    {
        // create prefix and return instance
        $this->prefix = $prefix;

        // return instance
        return $this;
    }

    /**
     * @method createSuffix
     * Register suffix and return class instance
     * @param string $suffix
     * @return GlobalConstants
     */
    public function createSuffix(string $suffix) : GlobalConstants
    {
        // create suffix and return instance
        $this->suffix = $suffix;

        // return instance
        return $this;
    }

    /**
     * @method getPrefix
     * get the active prefix or return null
     */
    public function getPrefix()
    {
        // return prefix from instance
        return $this->prefix;
    }

    /**
     * @method getSuffix
     * get the active suffix or return null
     */
    public function getSuffix()
    {
        // return prefix from instance
        return $this->suffix;
    }

    /**
     * @method newConstant
     * Create a new constant
     * @param ConstantInterface $constant
     * @return GlobalConstants
     */
    public function newConstant(ConstantInterface $constant) : GlobalConstants
    {
        // get name and value
        $this->name = $constant->getName();
        $this->value = $constant->getValue();

        // get instance
        $instance = $this->createConstantInstance($this->name);

        // set constants
        $instance->defineConstant($this->name, $this->value);

        // clean up
        unset($constant);

        // return class instance
        return $instance;
    }

    /**
     * @method fromConstant
     * Create a new constant from existing constant.
     * @param ConstantInterface $constant
     * @return GlobalConstants
     */
    public function fromConstant(ConstantInterface $constant) : GlobalConstants
    {
        // get constant value of base
        $constantValueOfBase = $this->constants[(string) $this->constant];

        // get name and value
        $this->name = $constant->getName();
        $this->value = $constant->getValue();

        // update value
        $value = $constantValueOfBase . $this->value;

        // get instance
        $instance = $this->createConstantInstance($this->name);

        // set constants
        $instance->defineConstant($this->name, $value);

        // clean up
        unset($constantValueOfBase, $value, $constant);

        // return instance for constant
        return $instance;
    }

    /**
     * @method GlobalConstants createConstantFromArray
     * @param mixed $constantArray
     * Create constant from array
     * @return GlobalConstants
     */
    public function createConstantFromArray(...$constantArray) : GlobalConstants
    {
        foreach ($constantArray as &$constant) :
        
            $this->fromConstant($constant);

        endforeach;

        // clean up
        unset($constantArray, $constant);

        // return class instance
        return $this;
    }

    /**
     * @method defineConstant
     * define constant if not defined previously
     * @param string $constant
     * @param string $value
     */
    private function defineConstant(string $constant, string $value)
    {
        // get suffix
        $suffix = (string) $this->getSuffix();

        // add to the list of constants added.
        $this->constants[$constant] = $value . $suffix;

        // add prefix
        $constantWithPrefix = strtoupper((string) $this->prefix . $constant);

        // create constant if not defined previously
        if (!defined($constantWithPrefix)) :
        
            // define constant now
            define($constantWithPrefix, $value);

            // add to the list of constants created
            // for global reference
            self::$constantCreated[strtolower($constant)] = $value;

        endif;

        // clean up
        unset($suffix, $constantWithPrefix, $value, $constant);
        
    }

    /**
     * @method createConstantInstance
     * create a fresh constant instance
     * @param string $constant
     * @return GlobalConstants
     */
    private function createConstantInstance(string $constant)
    {
        // create class instance
        $instance = new self;

        // set suffix and prefix
        $instance->createPrefix($this->prefix);
        $instance->createSuffix($this->suffix);

        // set active constant
        $instance->constant = $constant;

        // clean up
        unset($constant);

        return $instance;
    }

    /**
     * @method GlobalConstants getConstant
     * gets the value of a constant, otherwise return null
     * @param string $constant
     * @return mixed|null
     */
    public static function getConstant(string $constant)
    {
        // list of constants
        $constants = self::$constantCreated;

        // constant value
        $constantValue = null;

        // convert to lowercase
        $constant = strtolower($constant);

        // check if constant exists
        if (isset($constants[$constant])) :
        
            // get constant value
            $constantValue = $constants[$constant];
        
        endif;

        // clean up
        unset($constants, $constant);

        // return value
        return $constantValue;
    }

    /**
     * @method GlobalConstants magic function __set
     * We would use this to create a constant without prefix or suffix
     * @param string $constant_name
     * @param mixed $constant_value
     */
    public function __set(string $constant_name, $constant_value) : void
    {
        // serialize value if array
        $constant_value = is_array($constant_value) ? serialize($constant_value) : $constant_value;

        // set to uppercase
        $constant_name = strtoupper($constant_name);

        // define constant if not defined previously
        if (!defined($constant_name)) :

            // define constant
            define($constant_name, $constant_value);

        endif;
    }
}