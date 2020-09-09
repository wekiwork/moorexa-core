<?php
namespace Lightroom\Requests\Rules;

/**
 * @method RulesHelper
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait RulesHelper
{
    // push object
    public function pushObject($object=null, &$rules=[])
    {
        if (!is_null($object) && !is_string($object)) :
        
            // make object
            $object = is_array($object) ? func()->toObject($object) : $object;

            if (count($rules) > 0) :
            
                // ilterate
                array_map(function($key) use (&$object, &$rules)
                {
                    if (method_exists($object, 'row'))
                    {
                        $row = (array) $object->row();

                        if (isset($row[$key]))
                        {
                            $rules[$key]['default'] = $row[$key];
                        }
                    }
                    else
                    {
                        if (property_exists($object, $key))
                        {
                            $rules[$key]['default'] = $object->{$key};
                        }
                    }

                }, array_keys($rules));
            
            else:
            
                if (method_exists($object, 'row'))
                {
                    $row = (array) $object->row();

                    // create
                    foreach ($row as $key => $val)
                    {
                        $this->__rules__[$key]['default'] = $val;
                    }
                }
                else
                {
                    $className = get_class($object);

                    if ($className == 'stdClass')
                    {
                        foreach ($object as $key => $val)
                        {
                            $this->__rules__[$key]['default'] = $val;
                            $this->{$key} = $val;
                        }
                    }
                }

            endif;
        
        endif;
    }

    // set value
    public function setVal(string $ruleKey, $ruleData)
    {
        // set value
        $this->__rules__[$ruleKey]['value'] = $ruleData;

        // remove from error array
        unset($this->errors[$this->requestMethod()][$ruleKey]);
    }

    // pop methoD
    public function pop($key)
    {
        $arguments = func_get_args();

        if (count($arguments) > 1) :
        
            foreach ($arguments as $key) :
            
                if (isset($this->__rules__[$key])) :
                
                    unset($this->__rules__[$key]);

                    $this->freeFromCustomErrors($key);

                endif;
            
            endforeach;
        
        else:
        
            $data = $this->getRule($key);

            if (isset($this->__rules__[$key])) :
            
                unset($this->__rules__[$key]);

                $this->freeFromCustomErrors($key);

            endif;

            return $data;

        endif;
    }

    // has method
    public function _has(string $key) : bool
    {
        if ($this->hasProperty($key, $property)) :
        
            // check value
            $value = isset($property['value']) ? $property['value'] : null;

            if ($value !== null) return true;
            
        endif;

        // return boolean
        return false;
    }

    // clear rules
    public function clear($key=null)
    {
        $rules = $this->__rules__;
        $data = null;

        if (count($rules) > 0) :
        
            if ($key === null) :
            
                foreach ($rules as $name => $config) :
                
                    if (isset($config['default'])) $rules[$name]['default'] = null;

                    if (isset($config['value'])) $rules[$name]['value'] = null;
                
                endforeach;
            
            else:
            
                if (isset($rules[$key])) :
                
                    $data = $this->getRule($key);

                    // remove
                    $rules[$key]['default'] = null;
                    $rules[$key]['value'] = null;
                
                endif;
            
            endif;

            $this->__rules__ = $rules;
        
        endif;

        return $data;
    }

    // set identity method
    public function identity(string $key)
    {
        $value = $this->getRule($key);

        if (!is_null($value)) $this->identityCreated[$key] = $value;

        return $this;
    }

    // re validate
    public function revalidate($key, $value)
    {
        // get rule
        $rule = $this->__rules__[$key]['rule'];

        if ($this->isPassed($value, $rule, $errors)) :
        
            $this->freeFromCustomErrors($key);
        
        else:
        
            $this->errors[$this->flagRequest][$key] = $errors;

        endif;
    }

    // free from custom error
    private function freeFromCustomErrors($key)
    {
        // remove from error drum
        if (isset($this->errors[$this->requestMethod()][$key])) :
        
            unset($this->errors[$this->requestMethod()][$key]);

        endif;
    }

    // getter method
    public function __get($name)
    {
        if (isset($this->__rules__backup[$name])) :
        
            return $this->getBackupRule($name);
        
        else:
        
            return $this->getRule($name);

        endif;
    }

    // setter method
    public function __set(string $name, $val)
    {
        if (isset($this->__rules__[$name]))
        {
            $this->__rules__[$name]['value'] = $val;

            // re validate and remove from required
            $this->revalidate($name, $val);
        }
        else
        {
            // set new.
            $this->__rules__[$name]['value'] = $val;
            $this->__rules__[$name]['rule'] = null;
            $this->__rules__[$name]['default'] = null;

            // re validate and remove from required
            $this->revalidate($name, $val);
        }
    }

    // caller method
    public function __call(string $method, array $arguments)
    {
        if ($method == 'has') :
        
            return $this->_has($arguments[0]);
        
        elseif (isset($this->__rules__[$method])) :
        
            if (!isset($arguments[0])) :
            
                return $this->getRule($method);
            
            else:
            
                $this->__rules__[$method]['value'] = $arguments[0];

                // re validate and remove from required
                $this->revalidate($method, $arguments[0]);

            endif;

        endif;

        return $this;
    }

    // push data to rule
    public function pushData($data) : void
    {
        if (is_array($data) || is_object($data)) :
        
            foreach ($data as $key => $value) :
            
                if (is_string($key)) $this->{$key} = $value;
            
            endforeach;
             
        endif;
    }

    // is ok
    public function isOk()
    {
        $errors = $this->errors[$this->requestMethod()];

        if (count($errors) > 0) return false;

        return true;
    }

    // listen for error
    public function onError(string $title)
    {
        $error = $this->getError($title);

        $errorString = '';

        if (is_array($error)) :
        
            if (!post()->empty() || !get()->empty()) :
            
                $errorString = is_array($error[0]) ? implode("<br>", $error[0]) : $error[0];

            endif;
        
        elseif(is_string($error)) :
        
            $errorString = $error;

        endif;

        if ($errorString !== '') :
        
            return '<div style="align-self: flex-start;width: 100%;text-align: left;"><small class="text text-danger" style="text-align:left; margin-top:10px;">'.$errorString.'</small></div>';
        
        endif;

        return null;
    }

    // set errors
    public function setErrors($data)
    {
        $data = is_object($data) ? func()->toArray($data) : $data;
        $method = $this->requestMethod();
        $this->errors[$method] = $data;

        if (isset($data['errors']) && is_array($data['errors'])) :
        
            $this->errors[$method] = $data['errors'];
    
        elseif (isset($data['error']) && is_array($data['error'])) :
        
            $this->errors[$method] = $data['error'];

        endif;
    }
    
    // is passed
    public function isPassed($var, $flags, &$errors)
    {

        if (!is_object($var) && !is_array($var))
        {
            // set data
            $data = ['isPassed' => $var];

            // set flag
            $flag = ['isPassed' => $flags];

            // get validator
            $validator = $this->getValidator($data);

            // empty dump
            $errors = [];

            // run validator
            $validate = $validator->validate($flag, $errors);

            if ($validate) :
            
            if (isset($this->onSuccessBox[$var])) :
                
                    // call callback function
                    if (is_callable($this->onSuccessBox[$var])) :
                    
                        $value = '';

                        $data = [];
                        $data[] = $var;
                        $data[] = post();
                        $data[] = &$value;

                        call_user_func_array($this->onSuccessBox[$var], $data);

                        if (strlen($value) > 0) $this->__rules__[$var]['value'] = $value;

                endif;
                
            endif;

            return true;

        endif;

            $errors = $errors['isPassed'];
        }

        // return false
        return false;
    }
 
    // on success
    public function onSuccess($rules, $callback)
    {
        $rules = array_keys($rules);

        // get last
        if (count($rules) > 0) $this->onSuccessBox[end($rules)] = $callback;

        return $this;
    }

    // get errors
    public function getErrors()
    {
        return $this->getError();
    }

    // get error
    public function getError($name=null)
    {
        $errors = $this->errors[$this->requestMethod()];

        $errors = !is_array($errors) ? [] : $errors;

        if (!is_null($name))
        {
            if (isset($errors[$name]))
            {
                return $errors[$name];
            }

            return null;
        }

        return $errors;
    }

    // get values mirror
    public function getData()
    {
        return $this->rulesHasData();
    }

    // pick from rules
    public function pick()
    {
        $rules = func_get_args();

        // create rule
        $rule = new Input();

        // set validator
        $rule->validator = $this->validator;

        if (count($rules) > 0) :
        
            foreach ($rules as $key) $rule->{$key} = $this->getRule($key);

        endif;

        // load model
        $rule->model = new RuleModel;

        $rule->model->table = $this->model->table;

        $rule->model->setDatabase($this->model->getDatabase());

        // set input
        $rule->model->setInputInstance($rule);

        // create rule
        return $rule;

    } 

    // has property
    public function hasProperty($name, &$val)
    {
        if (isset($this->__rules__backup[$name])) :
    
            $val = $this->getBackupRule($name);

            return true;
        
        elseif (isset($this->__rules__[$name])) :
        
            $val = $this->__rules__[$name];

            return true;

        endif;

        return false;
    }
}