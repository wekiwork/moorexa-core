<?php
namespace Lightroom\Requests\Rules;

use Closure;
use Exception;
use Lightroom\Requests\Rules\Rule;
use Lightroom\Adapter\ClassManager;
use Lightroom\Requests\Rules\Interfaces\InputInterface;
use function Lightroom\Requests\Functions\{post, file, get};
/**
 * @package Input Rules helper
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait InputRulesTrait
{
    use RulesHelper;

    // avoid creating a fresh instance
    public static $hasRules = null;

    // rules created
    public $__rules__ = [];

    // useRules array
    public static $useRulesCreated = [];

    // errors occured
    public $errors = ['GET' => [], 'POST' => [], 'PUT' => [], 'DELETE' => []];

    // flag request
    public $flagRequest = 'NONE';

    // custom errors
    public $customErrors = [];

    // rules backup
    protected $__rules__backup = [];
    

    /**
     * @method InputRulesTrait getRule
     * @param string $name
     * @return mixed
     * 
     * This method gets a rule method
     */
    public function getRule(string $name)
    {
        // @var mixed $rule
        $returnRule = null;

        if (isset($this->__rules__[$name])) :
        
            $rule = $this->__rules__[$name];

            // return default;
            $returnRule = isset($rule['default']) ? $rule['default'] : null;

            if (isset($rule['value'])) $returnRule = $rule['value'];

        endif;

        return $returnRule;
    }

    /**
     * @method InputRulesTrait createRule
     * @return Rule
     */
    public function createRule() : Rule
    {
        return new Rule($this);
    }

    /**
     * @method InputRulesTrait useRule
     * @param string $ruleMethodName
     * @param mixed $object | ...$arguments
     * @return mixed
     * @throws Exception
     * 
     * Use a model rule
     */
    public function useRule(string $ruleMethodName, $object=null)
    {
        // update rule method name
        $ruleMethodName = ucfirst($ruleMethodName);

        // @var string $class
        $class = get_class($this->getClassInstance());

        if (!isset(self::$useRulesCreated[$ruleMethodName.'/'.$class])) :
        
            // create model rule
            $this->usingRule = true;

            // add set to method name
            $method = 'set'.$ruleMethodName;

            if (!method_exists($this->getClassInstance(), $method)) throw new Exception('Method '. $method . ' doesn\'t exist in "'.$class.'". Please check rule.');
            
            $createRules = $this->createRule();

            // @var array $arguments
            $arguments = func_get_args();

            // start from 1
            $arguments = array_splice($arguments, 1);

            // move rule to first index
            array_unshift($arguments, $createRules);
            
            // call method
            call_user_func_array([$this->getClassInstance(), $method], $arguments);
            

            if (is_object($object) && (get_class($object) == \PDOStatement::class || get_class($object) == 'stdClasss')) :
            
                // push object
                $this->pushObject($object, $createRules->rules);

            endif;

            // save rules.
            $this->__rules__ = $createRules->rules;

            // listen for http_request
            if (count($createRules->rules) > 0) $this->listenForHttpRequest();

            self::$useRulesCreated[$ruleMethodName.'/'.$class] = $this;
            
            // create reference
            $objNew =& $this;
        
        else:
        
            $objNew = self::$useRulesCreated[$ruleMethodName.'/'.$class];
        
        endif;

        // return instance
        return $objNew;
        
    }

    /**
     * @method InputRulesTrait ignoreRule
     * @param array $rules
     * @return InputInterface
     * 
     * This method ignore rules
     */
    public function ignoreRule(...$rules) : InputInterface
    {
        foreach ($rules as $rule) :
        
            if (isset($this->__rules__[$rule])) :
            
                // remove rule
                unset($this->__rules__[$rule]);

                // free from custom error
                $this->freeFromCustomErrors($rule);

            endif;
        
        endforeach;

        // return instance
        return $this;
    }

    /**
     * @method InputRulesTrait setRuleError
     * @param string $key
     * @param mixed $error
     * @return InputInterface
     * This method sets a rule error
     */
    public function setRuleError(string $key, $error) : InputInterface
    {
        if (isset($this->__rules__[$key])) $this->{$key} = '';

        $this->errors[$this->requestMethod()][$key] = $error;

        // return object
        return $this;
    }

    /**
     * @method InputRulesTrait rulesHasData
     * @return array
     * 
     * This method gets rules value.
     */
    public function rulesHasData() : array
    {
        // @var array $data
        $data = [];
        $rules = $this->__rules__;

        // read rules
        foreach ($rules as $key => $config) :
        
            // set data
            $data[$key] = $config['default'];

            if (isset($config['value'])) :
            
                $data[$key] = $config['value'];
            
            endif;

            // unset data
            if (isset($data[$key]) && is_null($data[$key])) unset($data[$key]);

        endforeach;

        // clean up
        $rules = null;

        // return data
        return $data;
    }

    // listen for request
    private function listenForHttpRequest()
    {
        $skip = [];

        // callbackClass
        $callbackClass = function($key)
        {
            return new class($key, $this)
            {
                private $key;
                private $object;

                public function __construct($key, &$object)
                {
                    $this->key = $key;
                    $this->object = $object;
                }

                public function setTo($value)
                {
                    $this->object->__rules__[$this->key]['value'] = $value;
                    $this->object->__rules__[$this->key]['rule'] = null;
                }
            };
        };

        // run for get
        $this->runRequestForGet($skip, $callbackClass);

        // run for post
        $this->runRequestForPost($skip, $callbackClass);

        // clean up
        $skip = null;
        $callbackClass = null;

    }

    private function requestMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * @method InputRulesTrait runRequestForGet
     * @param array $skip
     * @param Closure $callbackClass
     * @return void
     */
    private function runRequestForGet(array &$skip, Closure $callbackClass) : void
    {
        if (!get()->empty()) :
        
            // get
            $get = get();

            // get validator instance and load data
            $validator = $this->getValidator($get->all());

            // pass custom errors
            $validator->customErrors = $this->customErrors;

            foreach ($this->__rules__ as $key => $config) :
                
                if (!$get->has($key)) :

                    if ($config['default'] != null) :

                        $get->set($key, $config['default']);

                        // set data again
                        $validator->loadData($get->all());

                    endif;

                endif;

                if ($get->has($key)) :
                
                    if (!is_null($config['rule'])) :
                    
                        if ($this->flagRequest != 'GET') $validator->skipRequired = true;

                        $error = [];

                        $isValid = $validator->validate([$key => $config['rule']], $error);

                        // is valid ?
                        if ($isValid) :
                        
                            if (strlen($get->get($key)) > 0) :
                            
                                $this->__rules__[$key]['value'] = $get->get($key);

                                // skip in post
                                $skip[$key] = $get->get($key);

                            endif;

                            if (isset($this->onSuccessBox[$key])) :
                            
                                // call callback function
                                if (is_callable($this->onSuccessBox[$key])) :
                                
                                    $val = call_user_func($this->onSuccessBox[$key], $key, $get, $callbackClass($key));

                                    if ($val != null) :
                                    
                                        $this->__rules__[$key]['value'] = $val;
                                        $this->__rules__[$key]['rule'] = null;

                                    endif;

                                endif;
                            
                            endif;
                        
                        else:
                        
                            $this->errors['GET'][$key] = array_values($error);

                        endif;
                    
                    else:
                    
                        if (strlen($get->get($key)) > 0) $this->__rules__[$key]['value'] = $get->get($key);

                    endif;
                
                
                endif;

            endforeach;
        
        else:
        
            foreach ($this->__rules__ as $key => $config) :
            
                if (!is_null($config['rule'])) :
                
                    $rule = $config['rule'];

                    if ( (is_string($rule) && stripos($rule, 'required') !== false) && $config['default'] == null && $this->flagRequest == 'GET') :
                    
                        $this->errors['GET'][$key] = [$key . ' is required.'];
                        
                    endif;

                endif;

            endforeach;

        endif;
    }

    /**
     * @method InputRulesTrait runRequestForPost
     * @param array $skip
     * @param Closure $callbackClass
     * @return void
     */
    private function runRequestForPost(array &$skip, Closure $callbackClass) : void 
    {
        // @var string $requestMethod
        $requestMethod = $this->requestMethod();

        if (!post()->empty()) :
        
            // post
            $post = post();

            // get validator instance and load data
            $validator = $this->getValidator($post->all());

            // pass custom errors
            $validator->customErrors = $this->customErrors;

            $this->errors[$requestMethod] = [];

            foreach ($this->__rules__ as $key => $config) :

                if (!$post->has($key)) :

                    if ($config['default'] != null && $this->flagRequest == $requestMethod) :

                        $post->set($key, $config['default']);

                        // set data again
                        $validator->loadData($post->all());

                    endif;

                endif;
            
                if ($post->has($key) && !isset($skip[$key])) :
                
                    if (!is_null($config['rule'])) :
                    
                        if ($this->flagRequest != $requestMethod) $validator->skipRequired = true;

                        $error = [];

                        $isValid = $validator->validate([$key => $config['rule']], $error);

                        // is valid ?
                        if ($isValid) :
                        
                            if (strlen($post->get($key)) > 0) $this->__rules__[$key]['value'] = $post->get($key);

                            if (isset($this->onSuccessBox[$key])) :
                            
                                // call callback function
                                if (is_callable($this->onSuccessBox[$key])) :
                                
                                    $value = '';

                                    $data = [];
                                    $data[] = $key;
                                    $data[] = $post;
                                    $data[] = $callbackClass($key);

                                    $val = call_user_func_array($this->onSuccessBox[$key], $data);

                                    if ($val != null) :
                                    
                                        $this->__rules__[$key]['value'] = $val;
                                        $this->__rules__[$key]['rule'] = null;

                                    endif;

                                endif;

                            endif;
                        
                        else:
                        
                            $this->errors[$requestMethod][$key] = array_values($error);

                        endif;
                    
                    else:
                    
                        if (is_string($post->get($key)) && strlen($post->get($key)) > 0) :
                        
                            $this->__rules__[$key]['value'] = $post->get($key);
                        
                        elseif (is_array($post->get($key))) :
                        
                            $this->__rules__[$key]['value'] = $post->get($key);
                        
                        endif;
                    
                    endif;
                
                endif;

            endforeach;
        
        else:
        
            foreach ($this->__rules__ as $key => $config) :
            
                if (!is_null($config['rule'])) :
                
                    $rule = $config['rule'];

                    if (stripos($rule, 'required') !== false && $config['default'] == null && $this->flagRequest == $requestMethod) :
                    
                        $this->errors[$requestMethod][$key] = [$key . ' is required.'];

                    endif;
                
                endif;
            
            endforeach;

        endif;
    }
}