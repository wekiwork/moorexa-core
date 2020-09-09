<?php
namespace Lightroom\Requests\Functions;

/**
 * @package Request functions
 * @author Amadi ifeanyi <amadiify.com>
 */
use Closure;
use Lightroom\Requests\Interfaces\{
    CookieInterface, FilesInterface, GetRequestInterface, 
    HeadersInterface, PostRequestInterface, ServerInterface, 
    SessionInterface
};
use Lightroom\Exceptions\RequestManagerException;
use Lightroom\Requests\RequestManager;

/**
 * @method RequestManager cookie function
 * @param Closure $closure = null
 * @return CookieInterface|mixed
 *
 * A wrapper to cookie trait
 * @throws RequestManagerException
 */
function cookie(Closure $closure = null)
{
    // @var CookieInterface $cookie
    $cookie = RequestManager::cookieRequest();

    // load closure
    if ($closure !== null) return call_user_func($closure->bindTo($cookie, \get_class($cookie)));

    // return wrapper
    return $cookie;
}

/**
 * @method RequestManager session function
 * @param Closure $closure = null
 * @return SessionInterface|mixed
 *
 * A wrapper to session trait
 * @throws RequestManagerException
 */
function session(Closure $closure = null)
{
    // @var SessionInterface $session
    $session = RequestManager::sessionRequest();

    // load closure
    if ($closure !== null) return call_user_func($closure->bindTo($session, \get_class($session)));

    // return wrapper
    return $session;
}

/**
 * @method RequestManager get function
 * @param Closure $closure = null
 * @return GetRequestInterface|mixed
 *
 * A wrapper to get trait
 * @throws RequestManagerException
 */
function get(Closure $closure = null)
{
    // @var GetRequestInterface $get
    $get = RequestManager::getRequest();

    // load closure
    if ($closure !== null) return call_user_func($closure->bindTo($get, \get_class($get)));

    // return wrapper
    return $get;
}

/**
 * @method RequestManager post function
 * @param Closure $closure = null
 * @return PostRequestInterface|mixed
 *
 * A wrapper to post trait
 * @throws RequestManagerException
 */
function post(Closure $closure = null)
{
    // @var PostRequestInterface $post
    $post = RequestManager::postRequest();

    // load closure
    if ($closure !== null) return call_user_func($closure->bindTo($post, \get_class($post)));

    // return wrapper
    return $post;
}

/**
 * @method RequestManager header function
 * @param Closure $closure = null
 * @return HeadersInterface|mixed
 *
 * A wrapper to headers trait
 * @throws RequestManagerException
 */
function headers(Closure $closure = null)
{
    // @var HeadersInterface $header
    $header = RequestManager::headerRequest();

    // load closure
    if ($closure !== null) return call_user_func($closure->bindTo($header, \get_class($header)));

    // return wrapper
    return $header;
}

/**
 * @method RequestManager file function
 * @param Closure $closure = null
 * @return FilesInterface|mixed
 *
 * A wrapper to files trait
 * @throws RequestManagerException
 */
function files(Closure $closure = null)
{
    // @var FilesInterface $file
    $file = RequestManager::fileRequest();

    // load closure
    if ($closure !== null) return call_user_func($closure->bindTo($file, \get_class($file)));

    // return wrapper
    return $file;
}

/**
 * @method RequestManager server function
 * @param Closure $closure = null
 * @return ServerInterface|mixed
 *
 * A wrapper to server trait
 * @throws RequestManagerException
 */
function server(Closure $closure = null)
{
    // @var ServerInterface $server
    $server = RequestManager::serverRequest();

    // load closure
    if ($closure !== null) return call_user_func($closure->bindTo($server, \get_class($server)));

    // return wrapper
    return $server;
}
