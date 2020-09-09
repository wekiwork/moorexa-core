<?php
namespace Lightroom\Packager\Moorexa;

use Lightroom\Requests\Interfaces\{
    CookieInterface, FilesInterface, GetRequestInterface, 
    HeadersInterface, PostRequestInterface, 
    ServerInterface, SessionInterface, RequestManagerInterface
};
/**
 * @package MoorexaRequestManager 
 * @author Amadi ifeanyi <amadiify.com>
 * 
 * The default request manager for the moorexa framework
 */
class MoorexaRequestManager implements RequestManagerInterface
{
    /**
     * @var array $instanceCaches
     */
    private static $instanceCaches = [];

    /**
     * @method MoorexaRequestManager cookieRequest
     * @return CookieInterface
     */
    public static function cookieRequest() : CookieInterface
    {
        return self::getCacheOrCreateInstance('cookie');
    }

    /**
     * @method MoorexaRequestManager fileRequest
     * @return FilesInterface
     */
    public static function fileRequest() : FilesInterface
    {
        return self::getCacheOrCreateInstance('file');
    }

    /**
     * @method MoorexaRequestManager getRequest
     * @return GetRequestInterface
     */
    public static function getRequest() : GetRequestInterface
    {
        return self::getCacheOrCreateInstance('get');
    }

    /**
     * @method MoorexaRequestManager headerRequest
     * @return HeadersInterface
     */
    public static function headerRequest() : HeadersInterface
    {
        return self::getCacheOrCreateInstance('header');
    }

    /**
     * @method MoorexaRequestManager postRequest
     * @return PostRequestInterface
     */
    public static function postRequest() : PostRequestInterface
    {
        return self::getCacheOrCreateInstance('post');
    }

    /**
     * @method MoorexaRequestManager serverRequest
     * @return ServerInterface
     */
    public static function serverRequest() : ServerInterface
    {
        return self::getCacheOrCreateInstance('server');
    }

    /**
     * @method MoorexaRequestManager sessionRequest
     * @return SessionInterface
     */
    public static function sessionRequest() : SessionInterface
    {
        return self::getCacheOrCreateInstance('session');
    }

    /**
     * @method MoorexaRequestManager getCacheOrCreateInstance
     * @param string $requestType
     * @return mixed
     */
    private static function getCacheOrCreateInstance(string $requestType)
    {
        // instance
        $instance = null;

        // check if cached previously
        if (isset(self::$instanceCaches[$requestType])) :
            // get instance
            $instance = self::$instanceCaches[$requestType];
        endif;

        // execute switch statement if $instance is null
        if ($instance === null) :

            // run switch statement
            switch($requestType) :

                // session
                case 'session':
                    $instance = new class() implements SessionInterface { use \Lightroom\Requests\Session; };
                break;

                // cookie
                case 'cookie':
                    $instance = new class() implements CookieInterface { use \Lightroom\Requests\Cookies;};
                break;

                // server
                case 'server':
                    $instance = new class() implements ServerInterface { use \Lightroom\Requests\Server; };
                break;

                // post
                case 'post':
                    $instance = new class() implements PostRequestInterface { use \Lightroom\Requests\Post; };
                break;

                // get
                case 'get':
                    $instance = new class() implements GetRequestInterface { use \Lightroom\Requests\Get; };
                break;

                // header
                case 'header':
                    $instance = new class() implements HeadersInterface { use \Lightroom\Requests\Headers; };
                break;

                // file
                case 'file':
                    $instance = new class() implements FilesInterface { use \Lightroom\Requests\Files; };
                break;

            endswitch;

            // cache
            self::$instanceCaches[$requestType] = $instance;

        endif;

        // return instance
        return $instance;
    }
}