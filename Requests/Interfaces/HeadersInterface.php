<?php
namespace Lightroom\Requests\Interfaces;

/**
 * @package Headers Interface
 * @author Amadi Ifeanyi
 * 
 * This provides a quick abstraction for request and response headers
 */
interface HeadersInterface
{
    /**
     * @method HeadersInterface has header
     * @param string $header
     * @return bool
     * 
     * Checks to see if header exists. should return true if it does or false if otherwise
     */
    public function has(string $header) : bool;

    /**
     * @method HeadersInterface get header
     * @param string $header
     * @return string
     * 
     * Gets an header
     */
    public function get(string $header) : string;

    /**
     * @method HeadersInterface set header
     * @param string $header
     * @param string $value
     * @return void
     * 
     * Sets an header
     */
    public function set(string $header, string $value) : void;

    /**
     * @method HeadersInterface setMultiple
     * @param array $multiple
     * @return void
     * 
     * Sets multiple headers
     */
    public function setMultiple(array $multiple) : void;

    /**
     * @method HeadersInterface getCode
     * @return int
     * 
     * Gets the http_response_code
     */
    public function getCode() : int;

    /**
     * @method HeadersInterface setCode
     * @param int $code
     * @return void
     * 
     * Sets the http_response_code
     */
    public function setCode(int $code) : void;

    /**
     * @method HeadersInterface all headers
     * @return array
     * 
     * Gets all headers
     */
    public function all() : array;

    /**
     * @method HeadersInterface except
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from Headers except keys
     */
    public function except(...$keys) : array;

    /**
     * @method HeadersInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from Headers with respective keys
     */
    public function pick(...$keys) : array;

    /**
     * @method HeadersInterface allow headers
     * @param array $headers
     * @return void
     * 
     * Allow access to application from a list of headers if present.
     */
    public function allow(array $headers) : void;

    /**
     * @method HeadersInterface block headers
     * @param array $headers
     * @return void
     * 
     * Block access to the application from a list of headers
     */
    public function block(array $headers) : void;

    /**
     * @method HeadersInterface whitelistIp
     * @param array $ipaddresses
     * @return void
     * 
     * Block access to the application from a list of whitelisted ip addresses 
     */
    public function whitelistIp(array $ipaddresses) : void;

    /**
     * @method HeadersInterface empty
     * @return bool
     * 
     * Returns true if header is empty
     */
    public function empty() : bool;

    /**
     * @method HeadersInterface header_template
     * @param string $template_type
     * @param int $response_code
     * @return void
     */
    public function header_template(string $template_type, int $response_code) : void;

    /**
     * @method HeadersInterface clear
     * @return bool
     * 
     * Clears the HEADER array
     */
    public function clear() : bool;
}