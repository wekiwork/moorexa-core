<?php
namespace Lightroom\Requests\Interfaces;

use Lightroom\Requests\Interfaces\{
    CookieInterface, FilesInterface, GetRequestInterface,
    HeadersInterface, PostRequestInterface, ServerInterface, 
    SessionInterface
};
/**
 * @package Request Manager Interface
 * @author Amadi Ifeanyi
 */
interface RequestManagerInterface
{
    /**
     * @method RequestManagerInterface cookieRequest
     * @return CookieInterface
     */
    public static function cookieRequest() : CookieInterface;

    /**
     * @method RequestManagerInterface fileRequest
     * @return FilesInterface
     */
    public static function fileRequest() : FilesInterface;

    /**
     * @method RequestManagerInterface getRequest
     * @return GetRequestInterface
     */
    public static function getRequest() : GetRequestInterface;

    /**
     * @method RequestManagerInterface headerRequest
     * @return HeadersInterface
     */
    public static function headerRequest() : HeadersInterface;

    /**
     * @method RequestManagerInterface postRequest
     * @return PostRequestInterface
     */
    public static function postRequest() : PostRequestInterface;

    /**
     * @method RequestManagerInterface serverRequest
     * @return ServerInterface
     */
    public static function serverRequest() : ServerInterface;

    /**
     * @method RequestManagerInterface sessionRequest
     * @return SessionInterface
     */
    public static function sessionRequest() : SessionInterface;
}