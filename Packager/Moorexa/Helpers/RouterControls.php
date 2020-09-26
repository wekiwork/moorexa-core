<?php
namespace Lightroom\Packager\Moorexa\Helpers;

use Closure;
use Lightroom\Exceptions\ClassNotFound;
use Lightroom\Exceptions\InterfaceNotFound;
use Lightroom\Packager\Moorexa\Router;
use Lightroom\Adapter\ClassManager;
use ReflectionFunction, ReflectionMethod;
use Lightroom\Router\{
    RouterHandler, Middlewares, Guards
};
use Lightroom\Router\Interfaces\{
    RouterInterface, MiddlewareInterface
};
use Symfony\Component\Yaml\Yaml;
use Lightroom\Core\FunctionWrapper;

/**
 * @package Router Controls
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait RouterControls
{
    /**
     * @var array $routeRequests
     */
    public static $routeRequests = [];

    /**
     * @var array $requestUri
     */
    public  static $requestUri = [];

    /**
     * @var array $routeMatched
     */
    public static $routeMatched = [];

    /**
     * @var Router $instance
     */
    private static $instance = null;

    /**
     * @var bool $isMatched
     */
    private $isMatched = false;

    /**
     * @var array $request
     */
    private $request = [];

    /**
     * @var int $routeFound
     */
    private static $routeFound = 0;

    /**
     * @var string $currentRoute
     */
    private static $currentRoute = '';

    /**
     * @var array $routes
     */
    private static $routes = [];

    /**
     * @var array $callbackPromises
     */
    private static $callbackPromises = [];

    // load configuration
    public static function loadConfig() : array 
    {
        static $config;

        if ($config === null) :

            $config = Yaml::parseFile( get_path(SOURCE_BASE_PATH, '/config.yaml'));

        endif;

        return $config;
    }

    // read configuration
    public static function readConfig(string $config)
    {
        // @var array $configArray
        $configArray = explode('.', $config);

        // @var string returnValue
        $returnValue = '';

        if (count($configArray) > 0) :
        
            // @var array $configTree
            $configTree = self::loadConfig();

            // get value from config scanner
            $returnValue = self::readConfigScanner($configTree, $configArray);

            // update returnValue
            if ($config == 'router.default.controller' && $returnValue == '') $returnValue = '/';

        endif;

        // return mixed
        return $returnValue;
    }

    // get route matched
    public static function getRouteMatched() : array
    {
        return self::$routeMatched;
    }

    // set route matched
    public static function setRouteMatched(string $route) : void
    {
        self::$routeMatched = explode('/', $route);
    }

    // prepare middleware for route.
    public function middleware(string $middleware) : RouterInterface
    {   
        // load middleware if matched
        if (Router::$routeSatisfied) try {
            Middlewares::loadMiddleware($middleware, self::$routeMatched);
        } catch (ClassNotFound $e) {
        } catch (InterfaceNotFound $e) {
        }

        // return object.
        return $this;
    }

    // prepare guards for route
    public function guard(...$arguments) :  RouterInterface
    {
        // add route matched
        $arguments[] = self::$routeMatched;

        // load guard if matched
        if (Router::$routeSatisfied) :
            
            // get the return value from loadGuard
            $loadReturnValue = call_user_func_array([Guards::class, 'loadGuard'], $arguments);

            // update routeMatched if return value is an array
            if (is_array($loadReturnValue)) self::$routeMatched = $loadReturnValue;

        endif;

        // return object
        return $this;
    }

    // allow matching of route
    public static function reset() : void 
    {
        Router::$routeSatisfied = false;
        self::$routeFound = 0; 
        self::$instance->isMatched = false;

        // update Router Handler
        RouterHandler::resetRouter();
    }

    // call routes next to current route
    public static function next(string $route)
    {
        // return default route or call others
        return self::nextPrevHelper('next', $route);
    }

    // call routes previous to current route
    public static function prev(string $route)
    {
        // return default route or call others
        return self::nextPrevHelper('prev', $route);
    }

    // helper for next and prev buttons
    private static function nextPrevHelper(string $type, string $route)
    {
        // get array index
        $index = array_search(self::$currentRoute, array_keys(self::$routes));

        // get routes
        $routes = array_values(self::$routes);

        // get all from index
        $routes = ($type == 'next') ? array_splice($routes, $index+1) : array_splice($routes, 0, $index-1);

        // load routes
        foreach ($routes as $routeParam) :

            // reset route
            self::reset();

            // call execute method
            call_user_func_array([static::class, 'executeRoute'], $routeParam);

            // are we good ??
            if (self::$routeFound !== 0) return implode('/', self::$routeMatched);

        endforeach;

        // return default route
        return $route;
    }

    // find binds from complex paths
    private static function findBindInPath(string &$route, array &$regexpArray) : void
    {
        if (preg_match_all('/([{]([a-zA-Z0-9_-]*?)[}])/', $route, $matches1)) :
        
            foreach ($matches1[2] as $index => $bind) :
            
                // @var bool $optional
                $optional = false;

                // bind original
                $bindOriginal = $bind;

                // optional ?
                if (strrpos($bind,'?') !== false) :
                
                    // update optional
                    $optional = true;

                    // remove question mark
                    $bind = preg_replace('/[?]$/','',$bind);

                endif;

                // check if bind exists in $regexpArray
                if (isset($regexpArray[$bind])) :
                
                    // get expression
                    $expression = $regexpArray[$bind];

                else:
                
                    // @var string $expression
                    $expression = $optional ? '/([\S]*)/' : '/([\S]+)/';

                    // update $regexpArray
                    $regexpArray[$bind] = $expression;

                endif;

                // replace bind
                $route = str_replace('{'.$bindOriginal.'}', $expression, $route);

                // check again and call if match
                if (preg_match('/([{]([a-zA-Z0-9_-]*?)[}])/', $route)) :
                
                    // find binds again
                    self::findBindInPath($route, $regexpArray);

                endif;
            
            endforeach;

        endif;
    }

    // read config scanner
    private static function readConfigScanner($configTree, array $configArray)
    {
        if (count($configArray) != 0) :
        
            // @var string $child
            $child = array_shift($configArray);

            // check if $xml has child
            if (isset($configTree[$child])) :
            
                // read from config tree
                $configTree = self::readConfigScanner($configTree[$child], $configArray);
            
            else:
            
                $configTree = '';

            endif;

        endif;

        // return mixed
        return $configTree;
    }

    // has no closure function
    private static function hasClosureFunction(array &$arguments) : array
    {
        // get the last argument
        $lastArgument = end($arguments);

        if (is_string($lastArgument) && !is_callable($lastArgument)) :

            // create closure 
            $closure = function($request, $return)
            {
                return function() use (&$request, &$return)
                {
                    // get route requests
                    $route = isset(self::$routeRequests[$request]) ? self::$routeRequests[$request] : [];

                    // get return
                    foreach ($route as $key => $val) $return = preg_replace("/({($key)})/", $val, $return);

                    // return mixed
                    return $return;
                };
            };


            if (count($arguments) == 1) :
            
                $request = $return = $arguments[self::FIRST_PARAM];

                // update arguments
                $arguments[] = $closure($request, $return);
            
            else :
            
                $request = $arguments[self::FIRST_PARAM];
                $last = array_keys($arguments)[count($arguments)-1];
                $return = $arguments[$last];

                // update arguments
                $arguments[$last] = $closure($request, $return);
                
            endif;
        
        endif;

        // return array
        return $arguments;
    }

    // format arguments
    private static function formatArgs(array &$arguments)
    {
        /**
         * @var array $newArguments
         * #1 path
         * #2 callback
         * #3 regexp array
         */
        $newArguments = [];

        // @var int $arrayCount
        $arrayCount = 0;

        // run through
        foreach ($arguments as $index => $argument) :

            // set type
            switch (gettype($argument)) :
            
                // path
                case 'string':
                    $newArguments[self::FIRST_PARAM] = $argument;
                break;

                // case array
                case 'array':

                    // increment array count
                    $arrayCount++;

                    // can we continue
                    $newArguments[self::THIRD_PARAM] = $argument;

                break;

                // callback
                default:
                    // update new arguments
                    if ($argument !== null && is_callable($argument)) $newArguments[self::SECOND_PARAM] = $argument;

            endswitch;

        endforeach;

        // add regxparray
        if ($arrayCount > 1 && (isset($arguments[1]) && is_array($arguments[1]) )) $newArguments[] = $arguments[1];

        // switch to new args
        ksort($newArguments);

        // update arguments
        $arguments = $newArguments;
    }

    // execute route request
    private static function executeRoute(string $route, $callback, $regexpArray = [], $requestMethod = 'GET')
    {
        // @var string $currentRoute
        $currentRoute = $route . '_' . (time() * mt_rand(1,20));

        // store requests
        self::$routes[$currentRoute] = func_get_args();

        // create instance
        if (self::$instance === null) self::$instance = new self;

        // we dont have a match yet
        if (self::$routeFound > 0) self::$instance->isMatched = false;

        // manage regxp
        if (is_string($regexpArray)) : $requestMethod = $regexpArray; $regexpArray = []; endif;

        // continue if no route has been matched
        if (self::$routeFound == 0 && RouterHandler::routeNotFound()) :

            // get uri
            $uri = self::$requestUri;

            // set the request
            self::$instance->request = $uri;

            // not general
            if ($route !== '*') :

                // find url
                foreach ($uri as $index => $url) :

                    // replace space
                    if (preg_match('/[\s]+/', $url)) $uri[$index] = preg_replace('/[\s]+/', '+', $url);

                endforeach;

                // remove trailing /
                $route = preg_replace('/^[\/]/', '', $route);

                // check if path has opening brackets
                if (preg_match_all('/([(].*?[)]?(.*[)]))/', $route, $matches)) :
                
                    // push to regexp array
                    foreach ($matches[2] as $index => $match) :

                        // regexp key for match
                        $key = 'uri'.$index;

                        // remove trailing )
                        $match = preg_replace('/[)]$/','',$match);

                        // replace match with key
                        $route = str_replace('('.$match.')', '{'.$key.'}', $route);

                        // push to regexp array
                        $regexpArray[$key] = '('.$match.')';

                    endforeach;

                endif;

                // @var array $parameters
                $parameters = [];

                // @var bool $success
                $success = false;

                // get route array
                $routeArray = explode('/', $route);

                // get requests
                foreach ($routeArray as $index => $request) :

                    if (isset($uri[$index])) :
                    
                        // request passed from the browser at this index
                        $uriAtIndex = $uri[$index];

                        // replace {} with expression
                        // search for binding
                        if (preg_match_all('/([{]([\S\s]*?)[}])/', $request, $matches)) :
                        
                            // get bind
                            foreach ($matches[2] as $bind) :
                            
                                // @var bool $optional
                                $optional = false;

                                // bind original
                                $bindOriginal = $bind;

                                // optional ?
                                if (strrpos($bind,'?') !== false) :
                                
                                    // update optional
                                    $optional = true;

                                    // remove question mark
                                    $bind = preg_replace('/[?]$/','',$bind);

                                endif;

                                // check if bind exists in regexpArray
                                if (isset($regexpArray[$bind])) :
                                
                                    // get expression
                                    $expression = $regexpArray[$bind];

                                    // we run regexp here
                                    // first we replace $req on this index
                                    $request = str_replace('{'.$bindOriginal.'}', $expression, $request);

                                    // check for {} bind after str_replace
                                    self::findBindInPath($request, $regexpArray);
                                
                                else:
                                
                                    // @var string $expression
                                    $expression = $optional ? '/([\S]*)/' : '/([\S]+)/';

                                    // replace
                                    $request = str_replace('{'.$bindOriginal.'}', $expression, $request);

                                    // update regexpArray
                                    $regexpArray[$bind] = $expression;

                                endif;

                                // remove /( or /[ so we can make a proper regexp
                                $request = preg_replace('/([)|\]])\s{0}[\/]/','$1', preg_replace('/[\/]\s{0}([(|\[])/','$1', $request));

                                // quote request
                                $quoteRequest = str_replace('\\\\','\\', preg_replace('/(\\\{0}[\/])/','\/',$request));

                                // run regexp
                                $exec = preg_match_all("/^($quoteRequest)/i", $uriAtIndex, $match, 2);

                                // @var bool $usingLastResort
                                $usingLastResort = false;

                                // check if execution was successful
                                if ($exec === 0) :
                                
                                    if ($index == count($routeArray)-1) :
                                    
                                        // update uri at index
                                        $uriAtIndex = implode('/', self::$requestUri);

                                        // update $exec
                                        $exec = preg_match_all("/^($quoteRequest)/i", $uriAtIndex, $match, 2);

                                        // use last resort
                                        if ($exec) $usingLastResort = true;

                                    endif;

                                endif;

                                if ($exec) :
                                
                                    // best match
                                    $bestMatch = $match[0][0];

                                    if ($usingLastResort === false) :
                                    
                                        // get params
                                        $lastRequest = end($match[0]);

                                        // assign parameters
                                        if ($optional) :
                                        
                                            // update route array
                                            $routeArray[$index] = $bestMatch == '' ? ($uri[$index]) : $bestMatch;

                                            // update last request
                                            $lastRequest = $bestMatch == '' ? $routeArray[$index] : $lastRequest;
                                        
                                        else:
                                        
                                            $routeArray[$index] = $bestMatch;

                                        endif;

                                        // update parameter
                                        $parameters[$bind] = $lastRequest;
                                    
                                    else:
                                    
                                        // @var array $bestMatchArray
                                        $bestMatchArray = explode('/', $bestMatch);

                                        // @var int $current
                                        $current = $index;

                                        foreach($bestMatchArray as $bestMatchAtIndex) :
                                        
                                            if ($optional) :
                                            
                                                // update $routeArray
                                                $routeArray[$current] = $bestMatch == '' ? ($uri[$current]) : $bestMatchAtIndex;

                                                // update $result
                                                $result = $bestMatchAtIndex == '' ? $routeArray[$current] : $bestMatchAtIndex;
                                            
                                            else:
                                            
                                                // update $routeArray
                                                $routeArray[$current] = $bestMatchAtIndex;

                                            endif;

                                            // move cursor forward
                                            $current++;

                                        endforeach;

                                        $parameters[$bind] = $bestMatchArray;

                                    endif;

                                endif;

                            endforeach;
                        
                        else:
                        
                            // update route array
                            if ($uriAtIndex == $route) $routeArray[$index] = $uri[$index];

                        endif;
                    
                    else:
                    
                        // remove index.
                        unset($routeArray[$index]);

                        // set parameter to null.
                        array_push($parameters, null);

                    endif;

                endforeach;

            else:

                $parameters = [];
                $routeArray = $uri;

            endif;

            // now get call back params
            if ($callback !== null && (is_callable($callback) || is_array($callback) ))
            {
                if (is_array($callback)) :

                    // check class
                    if (is_string($callback[0]) && !class_exists($callback[0])) throw new ClassNotFound($callback[0]);

                    // get params 
                    $ref = new ReflectionMethod($callback[0], $callback[1]);

                    // @var ReflectionParameter $closureParameters
                    $closureParameters = $ref->getParameters();

                else:

                    // get params
                    $ref = new ReflectionFunction($callback);

                    // @var ReflectionParameter $closureParameters
                    $closureParameters = $ref->getParameters();
                
                endif;

                // save parameters
                self::$routeRequests[$route] = $parameters;

                // created parameters
                $newParameters = [];

                // get names
                foreach ($closureParameters as $index => $parameter) :

                    // get parameter name
                    $parameterName = $parameter->getName();

                    if (isset($parameters[$parameterName])) :
                    
                        // add to new parameters
                        $newParameters[$index] = $parameters[$parameterName];

                    else:

                        // get keys
                        $parametersKeys = array_keys($parameters);

                        if (isset($parametersKeys[$index]) && isset($parameters[$parametersKeys[$index]])) :
                        
                            if (is_array($parameters[$parametersKeys[$index]])) :
                            
                                // get $closureParameters
                                $closureParameters = $parameters[$parametersKeys[$index]];

                                // update $newParameters
                                foreach ($closureParameters as $i => $closureParameter) $newParameters[$i] = $closureParameter;
                            
                            else:
                            
                                // update $newParameters
                                $newParameters[$index] = $parameters[$parametersKeys[$index]];

                            endif;
                        
                        else:
                        
                            // update $newParameters
                            if (!isset($newParameters[$index])) $newParameters[$index] = null;

                        endif;

                    endif;

                endforeach;

                // set the new parameters
                if ($route == '*') $newParameters = [$uri];

                // home path
                $homePath = false;

                if (implode('/', $routeArray) == '/') :
                
                    if (count($uri) == 1) :
                    
                        // update $homePath
                        $homePath = true;

                        // update $newParameters
                        $newParameters = $uri;

                    endif;

                endif;

                // @var string $routeString
                $routeString = implode('/', $routeArray);
                
                // compare $pathUri with $uri
                // success
                if ( ($routeString == implode('/', $uri)) || $homePath == true)
                {
                    if ($routeString !== '') :

                        // matched!
                        self::$instance->isMatched = true;

                        // @var array $arguments
                        $arguments = [];

                        // class method
                        if (is_array($callback)) :

                            // get parameters
                            ClassManager::getParameters($callback[0], $callback[1], $arguments, $newParameters);

                        else:
                            
                            // get parameters
                            FunctionWrapper::getParameters($callback, $arguments, $newParameters);

                        endif;

                        // route was found
                        self::$routeFound++;

                        // all good
                        Router::$routeSatisfied = true;
                        self::$currentRoute = $currentRoute;

                        // update Router Handler
                        RouterHandler::routeFound();

                        // get returned route
                        $returnedRoute = function() use (&$callback, &$arguments)
                        {
                            // call closure function and get return value
                            $returnValue = preg_replace('/(^[\/])/', '', call_user_func_array($callback, $arguments));

                            // update RouterControls
                            self::$routeMatched = explode('/', $returnValue);
                        };

                        // build name
                        $name = $_SERVER['REQUEST_METHOD'] . '::' . $route;

                        // check if we have a promise for it
                        if (isset(self::$callbackPromises[$name])) :

                            // load callback
                            call_user_func(self::$callbackPromises[$name], $returnedRoute, self::$requestUri);

                        else:

                            // get returned route
                            $returnedRoute();

                        endif;

                        
                    endif;
                }
            }

        endif;

        // return instance
        return self::$instance;
    }
}