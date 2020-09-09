<?php
namespace Lightroom\Test;

use Lightroom\Exceptions\MethodNotFound;
/**
 * @package TestCase trait
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait TestCase
{
    use Assertions;

    /**
     * @var array $assertions
     */
    public static $assertions = [];

    /**
     * @var array $assertionsFailed
     */
    public static $assertionsFailed = [];

    /**
     * @var int $flag
     */
    private $flag = 1;

    /**
     * @method TestCase should
     * @param array|string $condition
     * @param mixed $data 
     * @return mixed
     */
    private function should($condition, $data = null) 
    {
        // update flag
        $this->flag = 1;

        // load method
        return $this->__testCaseLoader($condition, $data, 'Should');
    }

    /**
     * @method TestCase canExpect
     * @param array|string $condition
     * @param mixed $data 
     * @return mixed
     */
    private function canExpect($condition, $data = null) 
    {
        // update flag
        $this->flag = 2;

        // load method
        return $this->__testCaseLoader($condition, $data, 'Can Expect');
    }

    /**
     * @method TestCase __testCaseLoader
     * @param array|string $condition
     * @param mixed $data 
     * @param string $keyword
     * @return mixed
     */
    private function __testCaseLoader($condition, $data, string $keyword)
    {
        if (!is_array($condition)) $condition = [$condition, '0xx0xx'];

        // build method
        $method = 'assert_' . $condition[0];

        // check if method exists
        if (!\method_exists($this, $method)) throw new MethodNotFound(\get_class($this), $method);

        // clone condition
        $originalCondition = $condition;

        // get arguments
        $arguments = array_splice($condition, 1);

        // merge data
        $arguments = array_merge([&$data], $arguments);

        // add assertions
        $assertion = call_user_func_array([$this, $method], $arguments);

        // push
        self::$assertions[] = $assertion;

        // manage true or false
        foreach ($originalCondition as $index => $value) :
            $value = $value === true ? 'true' : $value;
            $value = $value === false ? 'false' : $value;
            $value = $value === '0xx0xx' ? 'true' : $value;
            $originalCondition[$index] = $value;
        endforeach;

        // update failed
        if ($assertion === false) :
            // push failed assertions
            self::$assertionsFailed[] = [
                'data' => ($data === false ? 'false' : ($data === true ? 'true' : ($data == null ? 'null' : $data))),
                'condition' => $keyword . ' (' . \implode(' ', $originalCondition) . ')'
            ];
        endif;

        // return assertion
        return $assertion;
    }
}