<?php
namespace Lightroom\Requests\Drivers;

use function Lightroom\Security\Functions\{encrypt};
use function Lightroom\Requests\Functions\{headers};
use function Lightroom\Functions\GlobalVariables\{var_set};
/**
 * @package DriversHelper
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait DriversHelper
{
    /**
     * @method DriversHelper getUserAgent
     * @return string
     * 
     * This method returns the user agent or build from server vars
     */
    public function getUserAgent() : string
    {
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        // hash agent
        $agent = strlen($agent) > 3 ? md5($agent) : $agent;

        // generate user agent
        if ($agent === '') :
        
            // get the user agent from the request header
            if (headers()->has('Request-Session-Token')) $agent = headers()->get('Request-Session-Token');

            // generate session id
            if ($agent === '') :

                // generate data from server info
                $server = func()->reduce_array($_SERVER);
                $server = array_values($server);
                $agent = md5(implode('/', $server));

            endif;

        endif;

        // make token global
        var_set('session_token', $agent);

        // send to header response
        header('Request-Session-Token: '.$agent);

        // return string
        return $agent;
    }

    /**
     * @method DriversHelper getKey
     * @param string $identifier
     * @param string $type
     * @return string
     */
    public function getKey(string $identifier, string $type = 'session') : string
    {
        // @var string $key
        $key = $identifier;

        // secret key
        if (is_string($identifier)) :
        
            // update key
            $key = substr(hash('sha256', '/'.$type.'/' . $identifier . '/key/' . env('bootstrap', 'secret_key')), 0, 10) . '_' . $identifier;

        endif;

        // return string
        return $key;
    }

    /**
     * @method DriversHelper getUserDomain
     * @param string $domain
     * @return string
     * 
     * This method returns the user domain
     */
    private function getUserDomain(string $domain) : string
    {
        // return hash 
        return md5($this->getUserAgent() . $domain);
    }

    /**
     * @method DriversHelper getSessionKey
     * @return string
     */
    private function getSessionKey() : string 
    {
        return md5(encrypt($this->getUserAgent()));
    }

    /**
     * @method DriversHelper getCookieKey
     * @return string
     */
    private function getCookieKey() : string 
    {
        return $this->getSessionKey();
    }
}