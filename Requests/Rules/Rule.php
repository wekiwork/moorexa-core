<?php
namespace Lightroom\Requests\Rules;

use Lightroom\Exceptions\{
    ClassNotFound, InterfaceNotFound
};
use function Lightroom\Requests\Functions\{post, file};
use Lightroom\Requests\Rules\Interfaces\InputInterface;
/**
 * @package Rule
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Rule 
{
    public $rules = [];
    private $input;
    private $flags = [];
    private $ruleAppliedGlobally = false;

    // constructor
    public function __construct(InputInterface &$input)
    {
        $this->input =& $input;
    }

    // call magic method
    public function __call(string $key, array $arguments)
    {
        if ($key == 'customErrors') :
        
            $this->input->customErrors = $arguments[0];
        
        elseif ($key == 'onSuccess') :
        
            array_unshift($arguments, $this->rules);
            call_user_func_array([$this->input, $key], $arguments);
        
        elseif ($key == 'flags' && is_array($arguments[0])) :
        
            $this->flags = $arguments[0];
        
        elseif ($key == 'allow_form_input' && count($arguments) == 0) :
        
            if (!post()->empty()) :
            
                // get all post data
                $post = post()->all();

                foreach ($post as $key => $value) :

                    $this->rules[$key] = [
                        'default' => null,
                        'rule' => null,
                        'value' => $value
                    ];

                endforeach;

                if (!file()->empty()) :
                
                    // get all files
                    $files = file()->all();

                    foreach ($files as $key => $value) :

                        if (isset($value->error) && $value->error == 0) :
                        
                            $this->rules[$key] = [
                                'default' => null,
                                'rule' => null,
                                'value' => $value
                            ];

                        endif;

                    endforeach;

                endif;

                $data = null;

            endif;
        
        elseif ($key == 'apply_global_rule') :
        
            $rule = $arguments[0];

            $this->ruleAppliedGlobally = true;

            foreach($this->rules as $value => $key) $this->rules[$key]['rule'] = $rule;
        
        elseif ($key == 'except' && $this->ruleAppliedGlobally) :
        
            if (count($arguments) > 0) :
            
                $this->ruleAppliedGlobally = false;

                foreach ($arguments as $key) $this->rules[$key]['rule'] = null;

            endif;
        
        else:
        
            if (isset($arguments[0])) :
            
                $rule = $arguments[0];

                if ($rule[0] == '@') :
                
                    $tag = substr($rule, 1);

                    if (isset($this->flags[$tag])) $rule = $this->flags[$tag];

                endif;

                $this->rules[$key] = [
                    'default' => isset($arguments[1]) ? $arguments[1] : null,
                    'rule' => $rule
                ];

                if (isset($arguments[1])) :
                
                    // apply rule
                    if (!is_null($rule) && strlen($rule) > 1)
                    {
                        $validator = $this->input->getValidator([$key => $arguments[1]]);

                        $error = [];

                        $isValid = $validator->validate([$key => $rule], $error);

                        if (!$isValid)
                        {
                            $this->input->errors[$_SERVER['REQUEST_METHOD']][$key] = array_values($error);
                        }
                    }

                endif;

            endif;

        endif;

        return $this;
    }

    /**
     * @method Rule validator
     * @param string $validatorClass
     * @return Rule
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     */
    public function validator(string $validatorClass) : Rule 
    {
        // set as active validator class
        $this->input->setValidator($validatorClass);

        // return class instance
        return $this;
    }

    /**
     * @method Rule getInput
     * @return InputInterface
     */
    public function getInput() : InputInterface
    {
        return $this->input;
    }

    // seter magic method
    public function __set(string $name, $val)
    {
        if (strtoupper($name) == 'FLAG_REQUIRED_IF') :
        
            $this->input->flagRequest = strtoupper($val);
            return false;

        endif;

        if ($name == 'table') :
        
            $this->input->model->table = $val;
            return false;

        endif;

        $this->rules[$name] = [
            'default' => $val,
            'rule' => null
        ];
    }
}