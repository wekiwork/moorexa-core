<?php
namespace Lightroom\Packager\Moorexa;

use Closure;
use Lightroom\Adapter\ClassManager;
use Lightroom\Packager\Moorexa\RouterMethods;
use Lightroom\Packager\Moorexa\Interfaces\ResourceInterface;
use Lightroom\Exceptions\{RequestManagerException, ClassNotFound, InterfaceNotFound, MethodNotFound};
use Lightroom\Router\Interfaces\RouterInterface;
use function Lightroom\Requests\Functions\server;
/**
 * @package Moorexa Router
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * The default router for moorexa controllers
 */
class Router implements RouterInterface
{
    use Helpers\RouterProperties, Helpers\RouterControls;

    /**
     * @var Router FIRST_PARAM
     */
    const FIRST_PARAM = 0;

    /**
     * @var Router SECOND_PARAM
     */
    const SECOND_PARAM = 1;

    /**
     * @var Router THIRD_PARAM
     */
    const THIRD_PARAM = 2;

    /**
     * @var RouterInterface $routerInstance
     */
    private static $routerInstance;

    /**
     * @var bool $routeSatisfied
     */
    public static $routeSatisfied = false;

    /**
     * @var array $functionsCreated
     */
    private static $functionsCreated = [];
 
    /**
     * @method Router any
     * @param array $arguments
     * @return mixed
     */
    public static function any(...$arguments)
    {
        // get the last argument
        $lastArgument = end($arguments);

        // get the request method
        try {
            self::$requestMethod = server()->get('request_method', 'get');
        } catch (RequestManagerException $e) {}

        // create a closure function
        if (!isset($arguments[(int) self::SECOND_PARAM])) $arguments[(int) self::SECOND_PARAM] = function(){};

        // no closure function
        self::hasClosureFunction($arguments);

        // format args
        self::formatArgs($arguments);

        // save route called
        self::$routesCalled[self::$requestMethod][] = $arguments[(int) self::FIRST_PARAM];

        // execute route
        $router = call_user_func_array([static::class, 'executeRoute'], $arguments);

        if (is_string($lastArgument)) self::$closureUsed[$lastArgument] = $arguments[(int) self::SECOND_PARAM];

        // return mixed
        return $router;
    }

    /**
     * @method Router request
     * @param string $methods
     * @param mixed $match
     * @param mixed $call 
     * @return mixed
     */
	public static function request(string $methods, $match = null, $call = null)
	{
        // @var array $arguments
		$arguments = \func_get_args();

        // get the lastArgument
		$lastArgument = end($arguments);

		// split
		$methods = explode('|', $methods);

        // @var bool $valid
        $valid = false;
        
        // get request method
		$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

		foreach ($methods as $requestMethod) :
		
            if ($requestMethod != '' && strtolower($requestMethod) == strtolower($method)) :
            
                // request is valid
                $valid = true;
                break;

            endif;
            
		endforeach;

		if ($valid === true) :
		
            switch ($call === null) :

                // closure function found
                case false:

                    // update request matched
                    self::$requestMatch = [];

                    // format args
                    self::formatArgs($arguments);

                    // add method
                    $arguments[] = $method;

                    // execute route
                    $router = call_user_func_array([static::class, 'executeRoute'], $arguments);

                    // check for function call
                    if (is_string($lastArgument)) self::$closureUsed[$lastArgument] = $call;

                    return $router;
    
                // no closure function
                case true:

                    return call_user_func($match);

            endswitch;
			
		endif;
    }
    
    /**
     * @method Router __callStatic
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments) 
    {
        // do we have a resolver ??
        if (preg_match('/^(resolve)/i', $method)) return self::resolver($method, $arguments);

        // get request method
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

        // @var mixed $router
        $router = null;

        // update method
        $method = strtoupper($method);

        // check if method is equivalent to the request method
        if ($method == $requestMethod) :

            // get the last argument
            $lastArgument = end($arguments);

            // get the first argument
            $firstArgument = $arguments[(int) self::FIRST_PARAM];

            // save route called
            self::$routesCalled[$method][] = $firstArgument;

            // check if it's string
            if (is_string($firstArgument)) :

                // check for function call
                if (is_string($lastArgument)) self::$closureName = $lastArgument;

                // no closure function
                self::hasClosureFunction($arguments);

                // format args
                self::formatArgs($arguments);

                // add method
                $arguments[] = $method;

                // execute route
                $router = call_user_func_array([static::class, 'executeRoute'], $arguments);

                // check for function call
                if (is_string($lastArgument)) :
                    
                    self::$closureUsed[$lastArgument] = $arguments[(int) self::SECOND_PARAM];

                endif;

            elseif (is_callable($firstArgument)) :

               // load closure
               call_user_func($firstArgument);

            endif;
                
        endif;

        // return mixed
        return $router == null ? ClassManager::singleton(Router::class) : $router;
    }

    /**
     * @method Router satisfy
     * @param string $route 
     * @param string $with
     * @return void
     */
    public static function satisfy(string $route, string $with) : void 
    {
        // @var array $routeArray
        $routeArray = explode('|', $route);

        // get route url
        $url = Router::$requestUri;

        // url string
        $urlString = implode('/', $url);

        // check route array
        foreach ($routeArray as $route) :

            // check route
            if ($route == $urlString) :

                // found
                Router::$requestUri = explode('/', $with);

                // break out
                break;

            else:

                foreach ($url as $index => $parameter) :

                    // match parameter
                    if ($parameter == $route) :

                        // found
                        $url[$index] = null;

                        // replace target
                        $with = str_replace('{target}', $route, $with);

                        // explode with
                        $with = explode('/', $with);

                        // splice value in
                        $before = array_splice($url, 0, $index);

                        // get url agein
                        $url = Router::$requestUri;

                        // get after the index
                        $after = array_splice($url, $index+1);

                        // remove from existsing
                        foreach ($with as $indexWith => $valWith) :

                            // remove from before if it exists
                            foreach ($before as $beforeVal) :

                                // check now and remove from with
                                if ($beforeVal == $valWith) unset($with[$indexWith]);

                            endforeach;

                        endforeach;

                        // combine all
                        Router::$requestUri = array_merge($before, $with, $after);

                        // break out
                        break;

                    endif;

                endforeach;

            endif;

        endforeach;
    }

    /**
     * @method Router createFunc
     * @param string $functionName
     * @param Closure $function
     * @return void 
     */
    public static function createFunc(string $functionName, Closure $function) : void
    {
        self::$functionsCreated[$functionName] = $function;
    }

    /**
     * @method Router callFunc
     * @param string $functionName
     * @param array $arguments
     * @return mixed 
     */
    public static function callFunc(string $functionName, ...$arguments)
    {
        if (isset(self::$functionsCreated[$functionName])) :
            return call_user_func_array(self::$functionsCreated[$functionName], $arguments);
        endif;
    }

    /**
     * @method Router resource
     * @param string $className
     * @return void
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws Exception
     * @throws MethodNotFound
     */
    public static function resource(string $className) : void 
    {
        // continue if only route hasn't been satisfied
        if (self::$routeFound === 0) :

            // does class exists ?
            if (!class_exists($className)) throw new ClassNotFound($className);

            // load reflection class and check for interface implementation
            $reflection = new \ReflectionClass($className);
            
            // check for interface
            if (!$reflection->implementsInterface(ResourceInterface::class)) throw new InterfaceNotFound($className, ResourceInterface::class);

            // get RouterMethods instance
            $routerMethods = ClassManager::singleton(RouterMethods::class);

            // reset it 
            $routerMethods->resetAll();

            // load class
            $instance = $reflection->newInstanceWithoutConstructor();

            // load trigger
            $instance->onRequest($routerMethods);

            // has triggers ?
            if ($routerMethods->hasMethods()) :

                // run through
                $methods = $routerMethods->getMethods();
                
                // so we check
                foreach ($methods as $httpMethod => $methodList) :

                    // can we stop checking for methodLists ?
                    if (self::$routeFound > 0) break;

                    // no let's keep checking
                    foreach ($methodList as $condition) :

                        // can we stop checking for conditions ?
                        if (self::$routeFound > 0) break;

                        // we keep checking
                        $classMethod = isset($condition[1]) ? $condition[1] : null;

                        // are we good 
                        if ($classMethod == null) throw new Exception('Missing Resource method for route #['+$condition[0]+']');

                        // check for method
                        if (!method_exists($instance, $classMethod)) throw new MethodNotFound($className, $classMethod);

                        // call method
                        call_user_func([static::class, $httpMethod], $condition[0], [$instance, $classMethod]);

                    endforeach;

                endforeach;

            endif;

        // end satisfied check
        endif;
    }

    /**
     * @method Router resolver
     * @param string $method
     * @param array $arguments
     * @throws Exception
     * @return void
     */
    public static function resolver(string $method, array $arguments) : void
    {
        // remove resolve from method
        $method = strtolower(preg_replace('/^(resolve)/i', '', $method));

        // resolver callback should be the last parameter
        $resolverCallback = array_pop($arguments);

        // get the request
        $request = isset($arguments[0]) ? $arguments[0] : null;

        // can we continue
        if ($request === null) throw new \Exception('Missing Route to match in Resolver.');

        // build request and add resolver callback function
        self::$callbackPromises[$_SERVER['REQUEST_METHOD'] . '::' . ltrim($request, '/')] = $resolverCallback;

        // load request now
        call_user_func_array([static::class, $method], $arguments);
    }
}