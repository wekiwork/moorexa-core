<?php
namespace Lightroom\Requests;

use Exception;
use Lightroom\Requests\Interfaces\HeadersInterface;
use function Lightroom\Requests\Functions\{server};

/**
 * @package Headers
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
trait Headers
{
    /**
     * @method HeadersInterface has header
     * @param string $header
     * @return bool
     * 
     * Checks to see if header exists. should return true if it does or false if otherwise
     */
    public function has(string $header) : bool
    {
        // get all headers
        $headers = $this->all();

        // convert to lowercase
        $header = strtolower($header);

        // has header bool
        $hasHeader = false;

        // check 
        if (isset($headers[$header])) :
            // update header bool
            $hasHeader = true;
        endif;

        // return bool
        return $hasHeader;
    }

    /**
     * @method HeadersInterface get header
     * @param string $header
     * @return string
     * 
     * Gets an header
     */
    public function get(string $header) : string
    {
        // get all headers
        $headers = $this->all();

        // convert to lowercase
        $header = strtolower($header);

        // return string
        return isset($headers[$header]) ? $headers[$header] : '';
    }

    /**
     * @method HeadersInterface set header
     * @param string $header
     * @param string $value
     * @return void
     * 
     * Sets an header
     */
    public function set(string $header, string $value) : void
    {
        // set header
        header($header . ': '. $value);
    }

    /**
     * @method Header __get
     * @param string $header 
     * @return string
     */
    public function __get(string $header) : string 
    {
        return $this->get($header);
    }

    /**
     * @method Headers except
     * @param array $keys 
     * @return array
     */
    public function except(...$keys) : array 
    {
        // @var array $picked
        $picked = $this->all();

        // using foreach loop
        foreach ($keys as $key) :

            // add to picked
            if (isset($picked[$key])) unset($picked[$key]);

        endforeach;

        // return array
        return $picked;
    }

    /**
     * @method Header __set
     * @param string $header
     * @param mixed $value 
     * @return string
     */
    public function __set(string $header, $value) 
    {
        $this->set($header, $value);
    }

    /**
     * @method HeadersInterface setMultiple
     * @param array $multiple
     * @return void
     * 
     * Sets multiple headers
     */
    public function setMultiple(array $multiple) : void
    {
        // using foreach loop
        foreach ($multiple as $header_key => $header_value) :

            // using $this->set();
            $this->set($header_key, $header_value);

        endforeach;
    }

    /**
     * @method HeadersInterface getCode
     * @return int
     * 
     * Gets the http_response_code
     */
    public function getCode() : int
    {
        // get response code
        return intval(http_response_code());
    }

    /**
     * @method HeadersInterface setCode
     * @param int $code
     * @return void
     * 
     * Sets the http_response_code
     */
    public function setCode(int $code) : void
    {
        // set code
        http_response_code($code);
    }

    /**
     * @method HeadersInterface all headers
     * @return array
     * 
     * Gets all headers
     */
    public function all() : array
    {
        // all headers
        $headers = [];
        
        // get headers from getallheaders()
        if (function_exists('getallheaders'))
        {
            // get all headers
            $allHeaders = getallheaders();

            // update keys, make lowercase
            foreach ($allHeaders as $key => $value):

                // make key lowercase
                $headers[strtolower($key)] = $value;

            endforeach;

            // add header list
            $headers['header_list'] = headers_list();
        }

        return $headers;
    }

    /**
     * @method Header pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from Headers with respective keys
     */
    public function pick(...$keys) : array
    {
        // @var array $newArray
        $newArray = [];

        // find keys
        foreach ($keys as $key) :

            // get value
            $value = $this->get($key);

            // push to new array if value was returned
            if ($value !== false) $newArray[$key] = $value;

        endforeach;

        // return new array
        return $newArray;
    }

    /**
     * @method HeadersInterface allow headers
     * @param array $headers
     * @return void
     * 
     * Allow access to application from a list of headers if present.
     */
    public function allow(array $headers) : void
    {
        // set allow headers
        $this->set('Access-Control-Allow-Headers', implode(',', $headers));
    }   

    /**
     * @method HeadersInterface block headers
     * @param array $headers
     * @return void
     * 
     * Block access to the application from a list of headers
     */
    public function block(array $headers) : void
    {
        // set block headers
        $this->set('Access-Control-Block-Headers', implode(',', $headers));
    }

    /**
     * @method HeadersInterface whitelistIp
     * @param array $ipAddress
     * @return void
     *
     * Block access to the application from a list of whitelisted ip addresses
     * @throws Exception
     */
    public function whitelistIp(array $ipAddress) : void
    {
        // get remote address
        $remoteAddr = server()->get('remote_addr');

        // run check
        foreach ($ipAddress as $address):
            // stop if remote address equals to address
            $quote = preg_quote($address);

            if (preg_match("/($quote)/", $remoteAddr) || $address == $remoteAddr) :

                // load access blocked template
                $this->header_template('accessblocked', 405);

                // stop here
                break;

            endif;

        endforeach;
    }

    /**
     * @method HeadersInterface empty
     * @return bool
     * 
     * Returns true if header is empty
     */
    public function empty() : bool
    {
        // get all headers
        $headers = $this->all();

        // headers list
        $list = isset($headers['header_list']) ? $headers['header_list'] : [];

        return count($list) > 0 ? false : true;
    }

    /**
     * @method header_template
     * @param string $template_type
     * @param int $response_code
     * @return void
     * @throws Exception
     */
    public function header_template(string $template_type, int $response_code) : void
    {
        // get the template file
        $template_file = '';

        // using the switch statement to get the template file
        switch ($template_type) :

            // access blocked
            case 'accessblocked' :
                $template_file = __DIR__ . '/Templates/accessblocked.html';
            break;

            // forbidden
            case 'forbidden':
                $template_file = __DIR__ .'/Templates/forbidden.html';
            break;

            // not allowed
            case 'notallowed':
                $template_file = __DIR__ . '/Templates/notallowed.html';
            break;

            // default
            default:
                // check if it's a file
                if (file_exists($template_type)) :
                    // set template_file
                    $template_file = $template_type;
                endif;

        endswitch;

        // if $template_file is empty or file doesn't exists, throw exception
        if ($template_file == '' || !file_exists($template_file)) :
            // throw exception
            throw new Exception('Header template file for "' . $template_type . '" does not exists.');
        endif;

        // clean output buffer
        ob_clean();

        // set response code
        $this->setCode($response_code);

        // include template file
        include_once $template_file;

        // kill process 
        die();

    }

    /**
     * @method HeaderInterface clear
     * @return bool
     * 
     * Clears the Header array
     */
    public function clear() : bool
    {
        // get all
        $all = $this->all();

        // get keys
        $keys = array_keys($all);

        // drop multiple
        call_user_func_array([$this, 'dropMultiple'], $keys);

        // clean up
        $all = null;

        // return boolean
        return true;
    }
}