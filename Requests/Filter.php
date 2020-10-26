<?php
namespace Lightroom\Requests;

use Lightroom\Events\Dispatcher;
use Lightroom\Adapter\ClassManager;
use Lightroom\Requests\Rules\Validator;
use Lightroom\Exceptions\InterfaceNotFound;
use Lightroom\Requests\Rules\Interfaces\ValidatorInterface;
use function Lightroom\Requests\Functions\{post, get, header as _header};
/**
 * @package Requests Filter
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Filter
{
    /**
     * @var mixed $channelUsed
     */
    private static $channelUsed = null;

    /**
     * @var bool $cleared
     */
    private static $cleared = false;
    
    /**
     * @method Filter apply
     * @param array $arguments
     * @return FilterPromise
     */
    public static function apply(...$arguments) : FilterPromise
    {
        // @var mixed $channel
        $channel = $arguments[0];

        // @var array $filterData
        $filterData = $arguments[1];

        // @var array $filter
        $filter = isset($arguments[2]) ? $arguments[2] : (is_array($filterData) ? $filterData : []);

        // turn off filter mechanisim
        env_set('bootstrap/filter-input', false);

        // load promise
        $promise = new FilterPromise();

        // get validator and filter data
        list($validator, $filterData) = self::getValidatorAndFilterData($channel, $filterData, $promise);

        // manage default values
        self::manageDefaultValues($filter, $filterData);

        // create instance of validator
        $validatorInstance = ClassManager::singleton($validator);

        // load data to validator
        $validatorInstance->loadData($filterData);

        // create pack
        $errors = [];

        // clean data
        $cleanData = [];

        // validate data
        $validatorInstance->validate($filter, $errors, $cleanData);

        // turn on filter mechanisim
        env_set('bootstrap/filter-input', true);

        // return new instance
        return $promise->filterReady($errors, $cleanData, $validator, $filter);
    }

    /**
     * @method Filter isOk
     * @return bool
     */
    public function isOk() : bool 
    {
        // @var bool $isOk
        $isOk = false;

        if (property_exists($this, 'errors')) :

            // check for errors
            if (count($this->errors) == 0) $isOk = true;

        endif;

        // return bool
        return $isOk;
    }

    /**
     * @method Filter data
     * @return array
     */
    public function data() : array
    {
        // @var array $data 
        $data = [];
        
        if ($this->isOk() && property_exists($this, 'cleanData')) $data = $this->cleanData;
        
        // return array
        return $data;
    }

    /**
     * @method Filter except
     * @param array $keys 
     * @return array
     */
    public function except(...$keys) : array 
    {
        // @var array $picked
        $picked = $this->data();

        // using foreach loop
        foreach ($keys as $key) :

            // add to picked
            if (isset($picked[$key])) unset($picked[$key]);

        endforeach;

        // return array
        return $picked;
    }

    /**
     * @method Filter pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from Filter with respective keys
     */
    public function pick(...$keys) : array
    {
        // get all 
        $get = $this->data();

        // @var array picked
        $picked = [];

        // using foreach loop
        foreach ($keys as $key) :

            // check if key exists
            $picked[$key] = isset($get[$key]) ? $get[$key] : false;

        endforeach;

        // return array
        return $picked;
    }

    /**
     * @method Filter has
     * @param string $key
     * @return array
     * 
     * Returns true if filter has a perticular key
     */
    public function has(string $key)
    {
        return (post()->has($key) || get()->has($key)) ? true : false;
    }

    /**
     * @method Filter clear
     * @return bool
     * 
     * Removes all entry from data and the filter method
     */
    public function clear() : bool 
    {
        // clear clean data
        if (property_exists($this, 'cleanData')) $this->cleanData = [];

        // errors
        $this->errors = [];
        self::$cleared = true;

        // clear from request handler
        $request = $this->getRequestHandler();

        // continue if not null
        if (is_object($request) && method_exists($request, 'clear')) $request->clear();

        // passed
        return true;
    }

    /**
     * @method Filter pop
     * @param array $arguments
     * @return bool
     * 
     * Removes one or more entry from data and the filter method
     */ 
    public function pop(...$arguments) : bool 
    {
        // @var bool $clean
        $clean = false;

        // check if property exists
        if (property_exists($this, 'cleanData')) :

            // remove
            foreach ($arguments as $key) :
                
                // check if data exists
                if (isset($this->cleanData[$key])) unset($this->cleanData[$key]);

            endforeach;

            // update bool
            $clean = true;

        endif;

        // clear from request handler
        $request = $this->getRequestHandler();

        // continue if not null
        if (is_object($request) && method_exists($request, 'dropMultiple')) call_user_func_array([$request, 'dropMultiple'], $arguments);

        // return bool
        return $clean;

    }

    /**
     * @method Filter get
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        // @var array $data 
        $data = $this->data();

        // check if data exists
        $data = isset($data[$name]) ? $data[$name] : null;
        
        // return mixed
        return $data;
    }

    /**
     * @method Filter getError
     * @param string $name
     * @return array
     */
    public function getError(string $name) : array 
    {
        // @var array $error 
        $error = [];

        if (self::$cleared === false && !$this->isOk()) :

            if (property_exists($this, 'errors') && isset($this->errors[$name])) :
                // load error
                $error = $this->errors[$name];
            endif;

        endif;

        // return array
        return $error;
    }

    /**
     * @method Filter getErrors
     * @return array
     */
    public function getErrors() : array 
    {
        return property_exists($this, 'errors') && self::$cleared === false ? $this->errors : [];
    }

    /**
     * @method Filter json 
     * @return string
     */
    public function json() : string 
    {
        return json_encode($this->data(), JSON_PRETTY_PRINT);
    }

    /**
     * @method Filter object 
     * @return object
     */
    public function object() 
    {
        return func()->toObject($this->data());
    }

    /**
     * @method Filter __get
     * @param string $name 
     * @return mixed 
     */
    public function __get(string $name) 
    {
        return $this->get($name);
    }

    /**
     * @method Filter __set
     * @param string $name
     * @param mixed $value 
     * @return Filter 
     */
    public function __set(string $name, $value) 
    {
        // add data
        if (property_exists($this, 'cleanData')) $this->cleanData[$name] = $value;

        // return instance
        return $this;
    }

    /**
     * @method Filter set
     * @param string $name
     * @param mixed $value 
     * @return Filter 
     */
    public function set(string $name, $value) 
    {
        // add data
        if (property_exists($this, 'cleanData')) $this->cleanData[$name] = $value;

        // return instance
        return $this;
    }

    /**
     * @method Filter filter
     * @param array $_filter
     * @return mixed
     * 
     * Apply filter on clean data
     */
    public function filter(array $_filter = [])
    {
        // @var Filter $instance
        $instance =& $this;

        // reset 
        self::$cleared = false;

        // load from old config
        if (property_exists($this, 'oldConfig')) :

            // @var array $filter
            $filter = $this->oldConfig['filter'];

            // try to load from _filter
            if (count($_filter) > 0) :

                // merge and make unique
                $filter = array_unique(array_merge($filter, $_filter));

            endif;

            // @var string $arguments
            $arguments = [
                $this->oldConfig['channel'],
                $this->cleanData,
                $filter
            ];

            // apply filter
            $instance = call_user_func_array([static::class, 'apply'], $arguments); 

        endif;
        
        // return instance
        return $instance;
    }

    /**
     * @method Filter showError
     * @return void
     * 
     * This triggers a 'filter.showError' event if filter encounted an error
     */
    public function showError() : void
    {
        // @var array $errors 
        $errors = $this->getErrors();

        // check if we have any error
        if (count($errors) > 0) :

            // trigger event
            Dispatcher::ev('filter.showError', $errors);

        endif;
    }

    /**
     * @method Filter getValidatorAndFilterData
     * @param mixed $channel
     * @param mixed $filterData
     * @param FilterPromise $promise
     * @return array
     */
    private static function getValidatorAndFilterData($channel, $filterData, FilterPromise $promise) : array
    {
        // get validator
        $validator = Validator::class;

        // load post data
        if (is_string($channel)) :

            switch (strtoupper($channel)) :

                case 'POST':
                case 'GET':
                case 'HEADER':
                    // load channel
                    self::loadChannel($channel, $filterData, $promise);
                break;

                // load validator
                default:

                    // check if class exists
                    if (class_exists($channel)) :
                        
                        // check if class implements the validator interface
                        $reflection = new \ReflectionClass($channel);

                        // throw exception
                        if (!$reflection->implementsInterface(ValidatorInterface::class)) throw new InterfaceNotFound($channel, ValidatorInterface::class);

                        // load validator
                        $validator = $channel;

                    endif;

            endswitch;

        else:

            // load array
            if (is_array($channel)) $filterData = $channel;

            // load from filter data
            if (is_string($filterData)) :

                switch (strtoupper($filterData)) :

                    case 'POST':
                    case 'GET':
                    case 'HEADER':
                        // load channel
                        self::loadChannel($filterData, $filterData, $promise);
                    break;

                    default:
                        $filterData = [];

                endswitch;

            endif;

        endif;

        // return array
        return [$validator, $filterData];
    }

    /**
     * @method Filter loadChannel
     * @param string $channel
     * @param array $filterData
     * @param FilterPromise $promise
     * @return void
     */
    private static function loadChannel(string $channel, array &$filterData, FilterPromise $promise) : void 
    {
        // load channel
        switch (strtoupper($channel)) :

            // POST
            case 'POST':

                // update channel
                $channelUsed = post();

                // load data
                $filterData = $channelUsed->input();

                // load handler
                $promise->setRequestHandler($channelUsed);

            break;

            // GET
            case 'GET':

                // update channel
                $channelUsed = get();

                // load data
                $filterData = $channelUsed->all();

                // load handler
                $promise->setRequestHandler($channelUsed);

            break;

            // HEADER
            case 'HEADER':
                
                // update channel
                $channelUsed = _header();

                // load data
                $filterData = $channelUsed->all();

                // load handler
                $promise->setRequestHandler($channelUsed);

            break;

        endswitch;
    }

    /**
     * @method Filter manageDefaultValues
     * @param array &$filter 
     * @param array &$filterData
     * @return void
     */
    private static function manageDefaultValues(array &$filter, array &$filterData) : void 
    {
        // let's check the filter array for array as rules
        foreach ($filter as $key => $rule) :

            // is rule an array
            if (is_array($rule)) :

                // @var bool $canContinue
                $canContinue = false;

                // check avaliablity
                if (!isset($filterData[$key]) && isset($rule[1])) $canContinue = true;

                // is value empty
                if (isset($filterData[$key]) && $filterData[$key] == '' && isset($rule[1])) $canContinue = true;

                // check if key exists in filter data
                if ($canContinue) $filterData[$key] = $rule[1];

                // convert to string only
                $filter[$key] = $rule[0];

            endif;

        endforeach;
    }
}