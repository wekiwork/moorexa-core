<?php
namespace Lightroom\Requests\Rules;

use Lightroom\Requests\Rules\Interfaces\ValidatorInterface;
/**
 *
 * @package Validator Class
 * @author Ifeanyi Amadi <www.amadiify.com>
 * @version 0.0.1
 **/
class Validator implements ValidatorInterface
{
    // custom errors
    public $customErrors = [];

    // skip required
    public $skipRequired = false;

    // error messages
    private $errors = [
        'min' => 'Invalid character length',
        'max' => 'Charater too long. Maximum length is {:length}',
        'alpha' => 'Must contain alphabets and numbers only',
        'number' => 'Must contain numbers only',
        'text' => 'Cannot contain anything other than alphabets only',
        'notag' => 'Cannot contain tags.',
        'symbols' => 'Must contain one or more symbols',
        'has' => 'Must contain {:flag} "{:contain}"',
        'email' => 'Invalid email address',
        'url'  => 'Invalid URL address',
        'date' => 'Invalid Date format',
        'match' => 'Does not match {:match}',
        'regxp' => 'Pattern failed.',
        'required' => 'Field is required. Value must contain at least one character, number or symbol',
        'file' => 'Must be a file',
        '_filetype' => 'Invalid file type. Must be of type {:contain}',
        'a_class' => 'Invalid Class name. Class does not exists',
        'an_array' => 'Must be of type array'
    ];

    // last method ran
    private $lastMethodRan = '';

    // failed
    private $failed = [];

    // success
    private $success = [];

    // private post data
    private $data = [];

    // private type
    private $type;

    /**
     * @method ValidatorInterface loadData
     * @param mixed $data 
     * @return void
     */
    public function loadData($data) : void
    {
        $this->type = is_object($data) ? 'object' : 'array';
        $this->data = is_object($data) ? fun()->toArray($data) : $data;
    }

    // a class
    private function a_class($str)
    {
        if (class_exists($str)) return true;

        return false;
    }

     // an array
     private function an_array($data)
     {
         if (is_array($data)) return true;
 
         return false;
     }

    // regxp
    private function regxp(string $str, $regxp)
    {
        if (preg_match($regxp, $str)) return true;

        return false;
    }

    // min
    private function min(string $str, $length = 2)
    {
        $len = mb_strlen($str);

        if ($len >= $length) return true;

        return false;
    }

    // file
    private function file(string $str)
    {
        if (isset($_FILES[$str])) return true;

        return false;
    }

    // file_multiple
    private function file_multiple(string $str)
    {
        // check for name
        if ($this->file($str)) :

            // if 'name' is an array then we are cool.
            return (is_array($_FILES[$str]['name'])) ? true : false;

        endif;

        // return default
        return false;
    }

    // file type
    private function _filetype(string $str, string $rule)
    {
        if ($this->file($str)) :
        
            $checkFileType = function($file, $fileType) use($str, $rule)
            {
                // get file name
                $filename = isset($file) ? explode('.', $file) : null;

                if (!is_null($filename)) :
                
                    // get type from rule
                    $rule = explode(',', $rule);

                    // has length
                    if (count($rule) > 0) :
                    
                        $found = false;

                        // get extension
                        $extension = !is_null($filename) ? end($filename) : '';

                        // lets run a loop
                        foreach ($rule as $index => $tp) :
                        
                            if (strtoupper($extension) == strtoupper($tp)) :
                            
                                $found = true;
                                break;

                            else:

                                // check file type
                                if (strpos($tp, '/') !== false) :

                                    // check
                                    if (strtolower($fileType) == strtolower($tp)) :
                                        $found = true;
                                        break;
                                    endif;

                                endif;

                            endif;
                        
                        endforeach;

                        // return bool
                        return $found;

                    endif;

                    // no rule defined.
                    // return true
                    return true;
                
                endif;
            };

            // check file
            if ($this->file_multiple($str)) :

                // @var int $success 
                $success = 0;

                // run loop
                foreach ($_FILES[$str]['name'] as $index => $file) if ($checkFileType($file, $_FILES[$str]['type'][$index])) $success++;
                
                // are we good ??
                if ($success == count($_FILES[$str]['name'])) return true;

                // failed
                return false;

            endif;

            // get the file name
            $fileName = (isset($_FILES[$str]['name']) ? $_FILES[$str]['name'] : '');

            // get the file type
            $fileType = (isset($_FILES[$str]['type']) ? $_FILES[$str]['type'] : '');

            // single file
            return $checkFileType($fileName, $fileType);

        endif;

        return false;
    }

    // max
    private function max(string $str, $length = 200)
    {
        $len = mb_strlen($str);

        if ($len > $length) return false;

        return true;
    }

    // alpha numeric characters only
    private function alpha(string $str)
    {
        if (preg_match("/([^a-zA-Z0-9\s]+)/", $str))
        {
            return false;
        }
        else
        {
            if (preg_match('/([a-zA-Z\s])/', $str) == true || preg_match('/([0-9])/', $str) == true)
            {
                return true;
            }
        }

        return false;
    }

    // number 
    private function number(string $str)
    {
        if (preg_match('/([^0-9]+)/', $str)) return false;

        return true;
    }

    // text => only letters
    private function text(string $str)
    {
        if (preg_match('/([^a-zA-Z\s]+)/', $str))
        {
            return false;
        }
        else
        {
            if (preg_match('/([a-zA-Z\s])/', $str) == true)
            {
                return true;
            }
        }

        return false;
    }

    // notag
    private function notag(string $str)
    {
        $uncode = html_entity_decode($str);

        if (preg_match("/[<]([a-zA-Z]{1,}+\s{0,1})([^>]+|)[>|]/m", $uncode))
        {
            return false;
        }

        return true;
    }

    // required
    private function required($str)
    {
        if (!$this->skipRequired)
        {
            if ((is_string($str) && strlen($str) > 0) || $str !== null)
            {
                return true;
            }

            return false;
        }

        return true;
    }

    // symbols
    private function symbols(string $str)
    {
        if (preg_match('/([^0-9a-zA-Z\s]+)/', $str)) return true;

        return false;
    }

    // date 
    private function _date(string $val, string $format)
    {
        $format = trim(preg_replace("/\s*[(]/", '', $format));
        $format = rtrim($format, ')');
        $datetime = new DateTime($val);
        $onFormat = $datetime->format($format);
        if (strcmp($val, $onFormat) === 0)
        {
            return true;
        }

        return false;
    }

    // has 
    private function has(string $str, $condition)
    {
        $condition = trim($condition);
        $as = null;
        
        switch($condition[0])
        {
            case '[':
                $as = 'or';
            break;

            case '{':
                $as = 'and';
            break;
        }
            
        $condition = substr($condition, 1, strlen($condition)-2);
        $split = str_split($condition);
        
        if ($as == 'or')
        {
            $total = count($split);
            $success = 0;

            foreach($split as $i => $r)
            {
                $reg = preg_quote("$r", '/');
                if (preg_match("/([$reg])/", $str))
                {
                    $success ++;
                }
            }

            if ($success > 0)
            {
                return true;
            }
        }
        else
        {
            $total = count($split);
            $success = 0;

            foreach($split as $i => $r)
            {
                $reg = preg_quote("$r", '/');
                if (preg_match("/([$reg])/", $str))
                {
                    $success ++;
                }
            }

            if ($total == $success)
            {
                return true;
            }

        }

        $contain = 'all of these ';

        if ($as == 'or')
        {
            $contain = 'any of these ';
        }

        $this->errors['has'] = str_replace('{:flag}', $contain, $this->errors['has']);

        return false;
    }

    // url
    private function url(string $str)
    {
        $url = filter_var($str, FILTER_VALIDATE_URL);

        if ($url !== false) return true;

        return false;
        
    }

    // email
    private function email(string $str)
    {
        if (preg_match("/[\s]/", $str))
        {
            return false;
        }
        else
        {
            if (preg_match("/[^a-zA-Z0-9\_\-\.\@]/", $str))
            {
                return false;
            }
            else
            {
                if (preg_match("/([^@]+)[@]{1}([^.|@]+)[.]\w{1,}/", $str))
                {
                    return true;
                }
            }
        }

        return false;
    }

    // match
    private function match(string $str, string $column)
    {
        if ($this->data[$column] != $str) return false;

        return true;
    }

    /**
     * @method Validator registerMethod
     * @param string $method
     * @return void
     */
    private function registerMethod(string $method) : void
    {
        // create a blank error
        if (!isset($this->errors[$method])) $this->errors[$method] = '';

        // update last method ran
        $this->lastMethodRan = $method;
    }

    /**
     * @method Validator setError
     * @param string $message
     * @return void 
     */
    protected function setError(string $message) : void 
    {
        if ($this->lastMethodRan != '') $this->errors[$this->lastMethodRan] = $message;
    }

    /**
     * @method ValidatorInterface validate
     * @param array $rules
     * @param array $errors
     * @param array $post
     */
    public function validate(array $array, array &$errors = [], array &$post = [])
    {
        if (is_array($this->data))
        {
            $private = ['validate', '__construct'];

            foreach ($array as $string => $options)
            {
                $options = explode("|", $options);
                
                foreach($options as $i => $option)
                {
                    if (strpos($option, ':') !== false)
                    {
                        // get first position
                        $pos = strpos($option, ':');
                        $meth = substr($option, 0, $pos);
                        $other = substr($option, $pos+1);
                    }
                    else
                    {
                        $meth = $option;
                        $other = null;
                    }

                    // switch method
                    switch ($meth)
                    {
                        case 'filetype':
                            $meth = '_filetype';
                        break;
                    }

                    if (method_exists($this, $meth) && !in_array($meth, $private))
                    {

                        if (array_key_exists($string, $this->data) || isset($_FILES[$string]))
                        {
                            $data = array_key_exists($string, $this->data) ? $this->data[$string] : $string;
                            
                            if (!is_null($data))
                            {
                                // register method
                                $this->registerMethod($meth);

                                // call method
                                $track = $this->{$meth}($data, $other);
                                
                                if ($track === false)
                                {
                                    if (isset($this->customErrors[$string.':'.$meth]))
                                    {
                                        $error = $this->customErrors[$string.':'.$meth];
                                    }
                                    else
                                    {
                                        $error = $this->errors[$meth];
                                    }

                                    $error = str_replace('{:length}', $other, $error);
                                    $error = str_replace('{:contain}', $other, $error);
                                    $error = str_replace('{:match}', $other, $error);

                                    $errors[$string][] = $error;
                                }
                                else
                                {
                                    $this->success[$string] = isset($this->data[$string]) ? $this->data[$string] : (isset($_FILES[$string]) ? $_FILES[$string] : null);
                                }
                            }
                            else
                            {
                                $errors[$string][] = 'Null value encounted. Rule ('.$meth.') didn\'t apply, validation failed';

                                // field is required
                                if ($meth == 'required') $errors[$string] = [$this->errors['required']];
                            }
                        }
                        else
                        {
                            $errors[$string][] = $string .' wasn\'t found. Rule ('.$meth.') didn\'t apply, validation failed';
                        }
                        
                    }
                }
            }

            if (count($errors) > 0) return false;

            //return post data
            $post = $this->success; 

            return true;
        }

        return false;
        
    }

    /**
     * @method Validator __call
     * @param string $method
     * @param array $arguments
     */
    public function __call(string $method, array $arguments)
    {
        if ($method == 'date') :
        
            return call_user_func_array([$this, '_date'], $arguments);
        
        elseif ($method == 'filetype') :
        
            return call_user_func_array([$this, '_filetype'], $arguments);

        endif;

        return null;
    }
}