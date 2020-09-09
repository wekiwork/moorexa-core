<?php
namespace Lightroom\Requests;

use Closure;
use Lightroom\Exceptions\{InterfaceNotFound, ClassNotFound, RequestManagerException};
use Lightroom\Requests\Interfaces\{
    CookieInterface, FilesInterface, GetRequestInterface, 
    HeadersInterface, PostRequestInterface, ServerInterface, 
    SessionInterface, RequestManagerInterface
};
use Lightroom\Core\Interfaces\PayloadProcess;
use ReflectionException;

/**
 * @package Request Manager for incoming http requests and server variables
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
class RequestManager implements RequestManagerInterface, PayloadProcess
{
    /**
     * @var RequestManager $defaultRequestManager
     */
    private static $defaultRequestManager = null;

    /**
     * @var Closure $next
     */
    private $next;

    /**
     * @method RequestManager constructor
     * Registers a default request manager and load request functions
     * @param string $requestManager
     * @param Closure $callback
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public function __construct(string $requestManager, Closure $callback = null)
    {
        // check if class exists
        if (!class_exists($requestManager))  throw new ClassNotFound($requestManager);

        // check if class does implement RequestManagerInterface
        $reflection = new \ReflectionClass($requestManager);

        if (!$reflection->implementsInterface(RequestManagerInterface::class))  throw new InterfaceNotFound($requestManager, RequestManagerInterface::class);

        // save request manager
        self::$defaultRequestManager = $requestManager;

        // include functions
        include_once __DIR__ . '/../Requests/Functions.php';

        // st closure
        $this->next = $callback;

        // clean up
        $reflection = $callback = null;
    }

    /**
     * @method RequestManager cookieRequest
     * @return CookieInterface
     * @throws RequestManagerException
     */
    public static function cookieRequest() : CookieInterface
    {
        return self::getDefault('cookieRequest');
    }

    /**
     * @method RequestManager fileRequest
     * @return FilesInterface
     * @throws RequestManagerException
     */
    public static function fileRequest() : FilesInterface
    {
        return self::getDefault('fileRequest');
    }

    /**
     * @method RequestManager getRequest
     * @return GetRequestInterface
     * @throws RequestManagerException
     */
    public static function getRequest() : GetRequestInterface
    {
        return self::getDefault('getRequest');
    }

    /**
     * @method RequestManager headerRequest
     * @return HeadersInterface
     * @throws RequestManagerException
     */
    public static function headerRequest() : HeadersInterface
    {
        return self::getDefault('headerRequest');
    }

    /**
     * @method RequestManager postRequest
     * @return PostRequestInterface
     * @throws RequestManagerException
     */
    public static function postRequest() : PostRequestInterface
    {
        return self::getDefault('postRequest');
    }

    /**
     * @method RequestManager serverRequest
     * @return ServerInterface
     * @throws RequestManagerException
     */
    public static function serverRequest() : ServerInterface
    {
        return self::getDefault('serverRequest');
    }

    /**
     * @method RequestManager sessionRequest
     * @return SessionInterface
     * @throws RequestManagerException
     */
    public static function sessionRequest() : SessionInterface
    {
        return self::getDefault('sessionRequest');
    }

    /**
     * @method RequestManager getDefault
     * @param string $method
     * @return mixed
     * @throws RequestManagerException
     */
    public static function getDefault(string $method) 
    {
        if (self::$defaultRequestManager === null) :
            // throw request manager exception
            throw new RequestManagerException();
        endif;

        // call static method from default request manager
        return call_user_func([self::$defaultRequestManager, $method]);
    }

    /**
     * @method RequestManager processComplete
     * @param Closure $next
     * @return void
     */
    public function processComplete(\Closure $next)
    {
        // call callback
        if (!is_null($this->next) and get_class($this->next) == Closure::class) :

            // call closure function
            call_user_func($this->next->bindTo((new class($next)
            {
                /**@var Closure $next */
                private $next;

                /**@method RequestManager constructor
                 * @param Closure $next
                 */
                public function __construct(\Closure $next)
                {
                    $this->next = $next;
                }

                // getter
                public function __get(string $name)
                {
                    // return instance
                    return RequestManager::getDefault($name . 'Request'); 
                }

                /**@method RequestManager next */
                public function next()
                {
                    if ($this->next !== null) call_user_func($this->next);
                }
            })));

        else :

            // call next payload
            $next();

        endif;
    }
}