<?php 
namespace Lightroom\Test;

use Exception;
use Lightroom\Packager\Moorexa\TestManager;
use Lightroom\Exceptions\{ClassNotFound, InterfaceNotFound, MethodNotFound};
use Lightroom\Adapter\ClassManager;
use Lightroom\Database\DatabaseHandler;
use Lightroom\Packager\Moorexa\Interfaces\ModelInterface;
use Lightroom\Packager\Moorexa\MVC\Helpers\ControllerViewHandler;
/**
 * @package RouteTest
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait RouteTest
{
    /**
     * @var int $code 
     * Http Response code
     */
    private $code = 0;

    /**
     * @var string $response 
     * Http response body
     */
    private $response = '';

    /**
     * @var array $info
     */
    private $info = [];

    /**
     * @method RouteTest ___buildHTTPBody
     * @param array $config
     * @return void 
     */
    public static function ___buildHTTPBody(array $config) : void
    {
        if (isset($config['http.body'])) :

            // manage request body
            foreach ($config['http.body'] as $requestMethod => $body) :

                // update $requestMethod
                $requestMethod = \strtoupper($requestMethod);

                // build file
                if ($requestMethod === 'FILE') :

                    // set the request method
                    $_SERVER['REQUEST_METHOD'] = 'POST';

                    // push file
                    foreach ($body as $field => $file) $_FILES[$field] = $file;

                else:

                    // set the request method
                    $_SERVER['REQUEST_METHOD'] = $requestMethod;

                    // set GET request body
                    if ($requestMethod == 'GET') $_GET = $body;

                    // set POST request body
                    if ($requestMethod != 'GET') $_POST = $body;

                endif;

            endforeach;

        endif;
    }
 
    /**
     * @method RouteTest request
     * @param string $method
     * @param string $route 
     * @param array $header 
     */
    private function request(string $method, string $route, array $body = []) 
    {
        // trim off backward slash
        $route = \ltrim($route, '/');

        // build http request from body
        self::___buildHTTPBody($body);

        // check for route url
        if (!isset(TestManager::$config['route_url'])) throw new Exception('Route url missing in test.yaml file.');

        // please ensure you have the route url set
        $route_url = TestManager::$config['route_url'];

        // do we have a valid url
        if (filter_var($route_url, FILTER_VALIDATE_URL) === false) throw new Exception('Invalid Route url "'.$route_url.'" in test.yaml file.');

        // using curl for the request
        $ch = curl_init($route_url . '/' . $route);

        // get host
        $url = parse_url($route_url);

        // headers
        $headers = array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Cache-Control: max-age=0',
            'Connection: keep-alive',
            'Host: '.$url['host'],
            'Upgrade-Insecure-Requests: 1'
        );

        // make curl options
        $curlOptions = [];

        if (isset($body['http.body'])) :

            // @var add post
            $post = [];

            // add post
            foreach ($body['http.body'] as $key => $data) if (strtolower($key) == 'post') $post = array_merge($post, $data);

            // add post
            if (count($post) > 0) :

                // make post submission
                $curlOptions[CURLOPT_POST] = 1;

                // add post data
                $curlOptions[CURLOPT_POSTFIELDS] = $post;

                // add content header
                $headers[] = 'Content-Type: multipart/form-data';

            endif;

        endif;

        if (isset($body['http.header'])) :

            // @var add header
            $header = [];

            // add post
            foreach ($body['http.header'] as $key => $value) $header[] = $key . ': ' . $value;

            // merge headers
            if (count($header) > 0) $headers = array_merge($header, $headers);

        endif;

        // make curl options
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,  
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ];

        // set options
        curl_setopt_array($ch, $curlOptions);

        // execute request
        $this->response = curl_exec($ch);

        // get curl info
        $info = curl_getinfo($ch);

        // set the info
        $this->info = $info;

        // set the response code
        $this->code = $info['http_code'];
        
        // return instance
        return $this;
    }

    /**
     * @method RouteTest ___loadIndex
     * @return void
     */
    private function ___loadIndex($method = 'GET', $include_method = 'once') : void 
    { 
        // set the request method
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);

        // hide html
        if (!defined('HIDE_HTML_OUTPUT')) define('HIDE_HTML_OUTPUT', true);

        // include the index file
        $include_method == 'once' ? include_once APPLICATION_ROOT . '/index.php' : include APPLICATION_ROOT . '/index.php';

        // get the content
        $this->response = isset($_SERVER['HTML_OUTPUT']) ? base64_decode($_SERVER['HTML_OUTPUT']) : '';

        // get the response code
        $this->code = http_response_code();
    }

    /**
     * @method RouteTest loadController
     * @param string $controller
     * @param array $config
     * @return mixed
     */
    private function loadController(string $controller, array $config = []) 
    {
        // load the index file
        $this->___loadIndex();

        // check namespace
        $controllerNamespace = 'Moorexa\Framework\\' . ControllerViewHandler::getNamespacePrefix() . ucfirst($controller);

        // check if controller exists
        if (!class_exists($controllerNamespace)) throw new ClassNotFound($controllerNamespace);

        // done
        return new class($controllerNamespace, $config)
        {
            /**
             * @var string $class
             */
            private $class = '';

            /**
             * @var array $config 
             */
            private $config = [];

            // load contructor
            public function __construct(string $class, array $config) 
            {
                // set class
                $this->class = $class;

                // load configuration
                \env_set('bootstrap/controller_config', $config);

                // cache config
                $this->config = $config;
            }

            // return class name
            public function name() : string 
            {
                return $this->class;
            }

            // load view
            public function loadView($view, ...$parameters)
            {
                if (is_string($view)) :

                    // get controller
                    if (!\property_exists($this, 'class')) : throw new Exception('Controller not defined.'); endif;
        
                    // controller class
                    $reflection = new \ReflectionClass($this->class);
        
                    // build incoming url
                    $incomingUrl = array_merge([basename(str_replace('\\', '/', $this->class)), $view], $parameters);

                    // load ControllerViewHandler without constructor
                    $handler = new \ReflectionClass(ControllerViewHandler::class);

                    // create instance
                    $handler = $handler->newInstanceWithoutConstructor();

                    // build http request from body
                    RouteTest::___buildHTTPBody($this->config);

                    // prepare view
                    return $handler->prepareView($incomingUrl, $reflection, function(){});

                endif;
            }
        };
        
    }

    /**
     * @method RouteTest loadModel
     * @param string $model
     * @param array $config
     * @return mixed
     */
    private function loadModel($model, array $config = [])
    {
        // load the index file
        $this->___loadIndex();

        // get model
        $modelClass = null;

        // get model class
        if (is_string($model) && class_exists($model)) $modelClass = $model;

        // get model from array
        if (\is_array($model) && count($model) > 1) : 

            // get controller and model
            $modelClass = 'Moorexa\Framework\\' . ControllerViewHandler::getNamespacePrefix() . ucfirst($model[0]) . '\Models\\' . ucfirst($model[1]);

        endif;

        // model class not null
        if ($modelClass === null) throw new Exception('Model class not assigned. Test failed');

        // check for class
        if (!class_exists($modelClass)) throw new ClassNotFound($modelClass);

        // create reflection class
        $reflection = new \ReflectionClass($modelClass);

        // check if model implements the model interface
        if (!$reflection->implementsInterface(ModelInterface::class)) throw new InterfaceNotFound($modelClass, ModelInterface::class);

        // load instance without constructor
        $model = $reflection->newInstanceWithoutConstructor();

        // return class
        return new class($model, $config)
        {
            /**
             * @var ModelInterface $model
             */
            private $model;
            
            /**
             * @var array $config
             */
            private $config = [];

            // load model to class
            public function __construct(ModelInterface $model, array $config)
            {
                $this->model = $model;
                $this->config = $config;
            }

            // call method
            public function __call(string $method, array $arguments)
            {
                // get the return value
                $returnValue = null;

                // get config and model
                $config = $this->config;
                $model = $this->model;

                // generate a unique id for this model
                $uniqueid = mt_rand(10, 4000) + intval(uniqid());

                // load method if allowed
                $call = function() use ($method, $arguments, &$returnValue, $model, $config, $uniqueid)
                {
                    if (method_exists($model, $method)) :

                        // extract from arguments
                        foreach ($arguments as $index => $argument) :

                            if (\is_object($argument) && method_exists($argument, 'getData')) :

                                if (isset($config['http.body']) && isset($config['http.body']['post'])) :
                                    $config['http.body']['post'] = $argument->getData();
                                else:
                                    $config['http.body'] = ['post' => $argument->getData()];
                                endif;

                                // remove index
                                unset($arguments[$index]);
                            endif;

                        endforeach;

                        // build http request from body
                        RouteTest::___buildHTTPBody($config);

                        // @var array $parameters
                        $parameters = [];

                        // get parameters using class manager
                        ClassManager::getParameters($model, $method, $parameters, $arguments);

                        // update unique identifier
                        DatabaseHandler::$queryUniqueid = $uniqueid;

                        // load model
                        $returnValue = call_user_func_array([$model, $method], $parameters);

                    else:

                        // method does not exists
                        throw new MethodNotFound(get_class($model), $method);

                    endif;
                };

                // load modelInit method
                $this->model->onModelInit($this->model, $call);

                // reset unique identifier
                DatabaseHandler::$queryUniqueid = 0;

                // return mixed
                return (object) [
                    'returnValue' => $returnValue,
                    'uniqueid' => $uniqueid
                ];
            }
        };
    }
}