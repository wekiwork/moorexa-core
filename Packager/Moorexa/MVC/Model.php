<?php
namespace Lightroom\Packager\Moorexa\MVC;

use Closure;
use Lightroom\Packager\Moorexa\{
    Interfaces\ModelInterface, MVC\Helpers\ControllerLoader,
    Helpers\UrlControls
};
use Lightroom\Packager\Moorexa\Helpers\URL;
use Lightroom\Adapter\ClassManager;
use Lightroom\Exceptions\RequestManagerException;
use function Lightroom\Requests\Functions\{
    post, server, get, header as _header
};
use function Lightroom\Common\Functions\{
    csrf_error, csrf_verified
};
use function Lightroom\Functions\GlobalVariables\{var_get, var_set};
/**
 * @package Moorexa Model
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * The default model handler for our MVC
 */
class Model implements ModelInterface
{
    /**
     * @var ControllerLoader $loader
     */
    private $loader;

    /**
     * @var string $requestMethod
     */
    private $requestMethod = '';

    /**
     * @var string $modelMethod
     */
    private $modelMethod = '';

    /**
     * @var bool $modelLoaded
     */
    private $modelLoaded = false;

    /**
     * @var array $arguments
     */
    private $arguments = [];

    /**
     * @var array $modelMethodReturnValues
     */
    private static $modelMethodReturnValues = [];

    /**
     * @method ModelInterface get
     * @param string $property
     * @return mixed
     * 
     * This method returns the value of a model property
     */
    public function get(string $property)
    {
        // check from modelMethodReturnValues
        if (isset(self::$modelMethodReturnValues[strtoupper($property)])) 
            
            // return mixed
            return self::$modelMethodReturnValues[strtoupper($property)];

        // check from class properties
        if (property_exists($this, $property)) return $this->{$property};
    }

    /**
     * @method ModelInterface set
     * @param string $property
     * @param mixed $value
     * @return void
     * 
     * This method sets the value of a model property
     */
    public function set(string $property, $value) : void
    {
        if (property_exists($this, $property)) $this->{$property} = $value;
    }

    /**
     * @method ModelInterface onModelInit
     * @param ModelInterface $model
     * @param Closure $next
     * @return void
     */
    public function onModelInit(ModelInterface $model, Closure $next) : void
    {
        // allow loading of view method
        $next();
    }

    /**
     * @method ModelInterface loadRequestMethodForView
     * @param ControllerLoader $loader
     * @param ModelInterface $model
     * @return void
     */
    public function loadRequestMethodForView(ControllerLoader $loader, ModelInterface $model) : void
    {
        // @var string $requestMethod
        $this->requestMethod = $this->getRequestMethod();

        // @var ControllerLoader $loader
        $this->loader = $loader;

        // update model method
        $this->modelMethod = $this->requestMethod . ucfirst($loader->getView());

        // get method
        $default = var_get('default.method');

        // update
        $this->modelMethod = is_string($default) && strlen($default) > 2 ? $default : $this->modelMethod;
        
        // update arguments
        $this->arguments = $loader->getArguments();

        // @var bool $loadViewMethod
        $loadViewMethod = false;

        // load init method
        $model->onModelInit($model, function() use (&$loadViewMethod){ $loadViewMethod = true; });

        // load the view method with request method
        if ($loadViewMethod) :

            // get csrf error
            $error = (function_exists('\Lightroom\Common\Functions\csrf_error') ? csrf_error() : '');

            // load method
            if ($error == '') :

                // load actionable
                if ($this->modelLoaded === false) $this->buildRequestFromActionable($model);

                // load model
                if ($this->modelLoaded === false && method_exists($model, $this->modelMethod)) $this->loadViewModel($model);

                // build model methods from request parameters
                if ($this->modelLoaded === false) $this->buildRequestMethodsFromParameters($model);

            endif;

        endif;
    }

    /**
     * @method Model http_request
     * @param string $requestMethods
     * @param string $view
     * @param Closure $modelClosure
     * @return void
     */
    public static function http_request(string $requestMethods, string $view, Closure $modelClosure) : void 
    {
        // @var bool $requestMatched
        static $requestMatched;

        if ($requestMatched === null) :

            // @var string $requestMethod
            $requestMethod = '';

            // get server http request method
            $httpMethod = $_SERVER['REQUEST_METHOD'];

            // convert to an array
            $requestMethods = explode('|', $requestMethods);

            // run check now
            foreach ($requestMethods as $method) :

                if (trim(strtolower($method)) == strtolower($httpMethod)) :

                    // load request method
                    $requestMethod = strtolower($method);

                    // break out
                    break;

                endif;

            endforeach;

            // load view model 
            if ($requestMethod != '') :

                // get all params sent
                $allGet = get()->all();

                // get all incoming url
                $url = URL::getIncomingUri();

                // get target
                $target = UrlControls::loadConfig()['beautiful_url_target'];

                // add app request
                if (isset($allGet[$target])) :

                    // get target array
                    $targetArray = explode('/', $allGet[$target]);

                    // merge both
                    $url = array_merge($url, $targetArray);

                endif;

                // load view array
                $viewArray = explode('|', $view);

                // flip url
                $url = array_flip($url);

                // check for match
                foreach ($viewArray as $view) :

                    // check match
                    if (isset($url[$view])) :

                        // request matched
                        $requestMatched = true;

                        // create anonymous class
                        $classObject = new class()
                        {
                            // setMethod
                            public function setMethod(string $method)
                            {
                                // set default method
                                var_set('default.method', $method);
                            }

                            // loadModel
                            public function loadModel(string $model)
                            {
                                // set default model
                                var_set('default.model', $model);
                            }   
                        };

                        // update view
                        $view = preg_replace('/(\s+)/', '', ucwords(str_replace('-', ' ', $view)));

                        // call closure
                        call_user_func($modelClosure->bindTo($classObject, \get_class($classObject)), $requestMethod, $view);

                        // break out
                        break;

                    endif;

                endforeach;

            endif;

        endif;
    }

    /**
     * @method Model request
     * @param string $type  
     * @return array
     */
    protected function request(string $type) : array 
    {
        // @var array $returnData
        $returnData = [];

        // load all data
        switch (strtoupper($type)) :

            // POST
            case 'POST' :
                $returnData = post()->input();
            break;

            // GET 
            case 'GET':
                $returnData = get()->all();
            break;

            // HEADER
            case 'HEADER':
                $returnData = _header()->all();
            break;

            default:
                $returnData = isset($_REQUEST[strtoupper($type)]) ? $_REQUEST[strtoupper($type)] : [];

        endswitch;

        // return array
        return $returnData;
    }

    /**
     * @method Model viewVar
     * @param string $key
     * @param mixed $value
     * @return Model
     */
    protected function viewVar(string $key, $value) 
    {
        // set view variable
        Controller::setViewVars($key, $value);

        // return instance
        return $this;
    }

    /**
     * @method Model getRequestMethod
     * @return string
     * @throws RequestManagerException
     */
    private function getRequestMethod() : string 
    {
        // @var string $method
        $method = strtolower(server()->get('request_method', 'get'));

        // update method from POST
        $method = post(function() use ($method)
        {
            // @var string $method
            $method = $this->get('REQUEST_METHOD', $method);

            // drop
            $this->drop('REQUEST_METHOD');

            // return string
            return $method;
        });

        // return string
        return $method;
    }

    /**
     * @method Model buildRequestMethodsFromParameters
     * @param ModelInterface $model
     * @return void
     */
    private function buildRequestMethodsFromParameters(ModelInterface $model) : void 
    {
        // @var closure $url
        $url = function() : array {

            // load incoming uri
            $url = Url::getIncomingUri();

            // return array
            return array_splice($url, 1);
        };

        // @var array $arguments
        $arguments = $url();

        // @var array $modelChain
        $modelChain = [];

        // @var array $modelChainArguments
        $modelChainArguments = [];

        // @var bool seen
        $seen = false;

        // check the request method
        if (strpos($this->requestMethod, '@') === 0) :

            // @var string $method
            $method = substr($this->requestMethod, 1);

            // get arguments from index 1
            $arguments = array_splice($arguments, 1);

            // check if method exists in model class
            if (method_exists($model, $method)) :

                // set arguments
                $this->arguments = $arguments;

                // update modelMethod
                $this->modelMethod = $method;

                // update seen 
                $seen = true;

                // load method
                $this->loadViewModel($model);

            endif;


            // cancel further checking 
            $arguments =  [];

        endif;

        // continue if $arguments size is greater than zero
        if (count($arguments) > 0) :

            // run foreach loop
            foreach($arguments as $index => $argument) :

                // @var array $argumentsNew
                $argumentsNew = $url();

                // @var int $pointer
                $pointer = $index + 1;

                // remove numbers
                if (preg_match('/[^a-zA-Z\-]/', $argument)) $argument = ''; ($pointer + 1);

                // @var array $modelArguments
                $modelArguments = array_splice($argumentsNew, $pointer);

                // remove - from argument
                if (strpos($argument, '-') !== false) $argument = lcfirst(str_replace(' ','', ucwords(str_replace('-',' ', $argument))));

                // add to model chain
                if ($argument !== '') :

                    // add model chain
                    $modelChain[$index] = $argument;

                    // @var string $classMethod
                    $classMethod = $this->requestMethod . ucwords(implode(' ', $modelChain));

                    // remove white spaces
                    $classMethod = preg_replace('/(\s*)/', '', $classMethod);

                    // remove actions
                    $modelArguments = $this->removeActionsFromArguments($modelArguments);

                    // add to chain
                    $modelChainArguments[$index] = $modelArguments;

                    // check if method exists in model class
                    if (method_exists($model, $classMethod)) :

                        // set arguments
                        $this->arguments = $modelArguments;

                        // update modelMethod
                        $this->modelMethod = $classMethod;

                        // update seen 
                        $seen = true;

                        // load method
                        $this->loadViewModel($model);

                        // break out.
                        break;

                    endif;

                endif;

            endforeach;

            // load one more check if method not seen
            if ($seen === false) :

                // get the last
                if (count($modelChain) > 1) :

                    $modelChain = array_splice($modelChain, 1);

                    foreach ($modelChain as $index => $method) :

                        // @var string $classMethod
                        $classMethod = $this->requestMethod . ucwords($method);

                        // check if method exists in model class
                        if (method_exists($model, $classMethod)) :

                            // load arguments
                            $modelArguments = $modelChainArguments[($index + 1)];

                            // set arguments
                            $this->arguments = $modelArguments;

                            // update modelMethod
                            $this->modelMethod = $classMethod;

                            // load method
                            $this->loadViewModel($model);

                            // break out
                            break;

                        endif;

                    endforeach;

                endif;

            endif;

        endif;
    }

    /**
     * @method Model buildRequestFromActionable
     * @param ModelInterface $model
     * @return void
     */
    private function buildRequestFromActionable(ModelInterface $model) : void 
    {
        if (property_exists($model, 'actionable')) :

            // get actionables
            $actionables = $model->actionable;

            // @var closure $url
            $url = function() : array {

                // load incoming uri
                $url = Url::getIncomingUri();

                // return array
                return array_splice($url, 1);
            };

            // run through the list of actionables
            foreach ($actionables as $action => $option) :

                // @var array $actionArray
                $actionArray = explode('/', $action);

                // @var string $target
                $target = '';

                // get url
                $getUrl = $url();

                // load target
                $target = isset($getUrl[1]) && ($action == $getUrl[1]) ? $action : '';

                // has /
                if (count($actionArray) > 1) :

                    // get action from url at length
                    $urlAtAction = array_splice($getUrl, 0, count($actionArray));

                    // check for a match
                    if (implode('/', $urlAtAction) == $action) $target = $action;

                endif;

                // can we continue
                if ($target != '') :

                    // run options
                    foreach ($option as $requestMethod => $modelMethod) :

                        // check request method
                        if (strtoupper($this->requestMethod) == strtoupper($requestMethod)) :

                            // check if method exists
                            if (method_exists($model, $modelMethod)) :

                                // stop further execution
                                $this->modelLoaded = true;

                                // get url
                                $getUrl = $url();

                                // get arguments
                                $arguments = array_splice($getUrl, count($actionArray));

                                // set arguments
                                $this->arguments = $arguments;

                                // update modelMethod
                                $this->modelMethod = $modelMethod;

                                // load method
                                $this->loadViewModel($model);

                            endif;

                            // break out
                            break;
                            
                        endif;

                    endforeach;

                    // break out
                    break;

                endif;

            endforeach;

        endif;
    }

    /**
     * @method Model loadViewModel
     * @param ModelInterface $model
     * @return void
     */
    private function loadViewModel(ModelInterface $model) : void 
    {
        // update modelLoaded 
        $this->modelLoaded = true;

        // @var array $parameters
        $parameters = [];

        // @var array $arguments
        $arguments = $this->removeActionsFromArguments($this->arguments);

        // get all parameters
        ClassManager::getParameters($model, $this->modelMethod, $parameters, $arguments);

        // trigger model.ready event
        if (event()->canEmit('ev.model.ready')) event()->emit('ev', 'model.ready', [
            'model' => &$model,
            'method' => &$this->modelMethod,
            'parameters' => &$parameters
        ]);

        // @var string $target
        $target = strtoupper($this->modelMethod . '.return');

        // call model and save the return value
        self::$modelMethodReturnValues[$target] = call_user_func_array([$model, $this->modelMethod], $parameters);
    }

    /**
     * @method Model removeActionsFromArguments
     * @param array $arguments
     * @return array
     */
    private function removeActionsFromArguments(array $arguments) : array 
    {
        static $actions;

        // load actions
        if (is_null($actions)) $actions = UrlControls::loadConfig()['actions'];

        // load action
        foreach ($actions as $action) :

            if (isset($arguments[0]) && $arguments[0] == $action) :

                // remove the first index
                array_shift($arguments);

                // break out
                break;

            endif;

        endforeach;

        // return array
        return $arguments;
    }
}
