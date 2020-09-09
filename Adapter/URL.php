<?php
namespace Lightroom\Adapter;

use Lightroom\Adapter\Configuration\Interfaces\URLInterface;

/**
 * @package URL Wrapper trait
 * @author amadi ifeanyi <amadiify.com>
 * Base class must implement URLInterface
 */
trait URL
{
    /**
     * @var string $currentUrl
     */
    private $currentUrl = '';

    /**
     * @var string $defaultScheme
     */
    private $defaultScheme = 'http';

    /**
     * @method URLInterface set current url
     *
     * Sets the current url for the app.
     * @param string $url
     */
    public function setUrl(string $url) : void
    {
        $this->currentUrl = $url;
    }

    /**
     * @method URLInterface get current url
     * 
     * Returns the current url saved.
     * @return string
     */
    public function getUrl() : string
    {
        // return url
        return $this->currentUrl != '' ? $this->currentUrl : $this->getUrlFromServer();
    }

    /**
     * @method URLInterface get current url for paths
     * 
     * Returns the current url saved plus root directory.
     * @return string
     */
    public function getPathUrl() : string
    {
        // get url
        $pathUrl = $this->getUrl();

        if (function_exists('env') && env('bootstrap', 'app.url') == '') :

            // get scheme and host
            $url = parse_url($pathUrl);

            // check if scheme exists
            if (isset($url['scheme'])) :
            
                // reset default behaviour
                $url['scheme'] = $url['scheme'] . '://';

                // add port to host
                $url['host'] = $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');

                // get root directory
                $pathUrl = $this->getRootDirectoryForUrlIfLocal($url);
            
            endif;

        endif;

        // return url
        return $pathUrl;
    }

    /**
     * @method URLInterface get current url
     * 
     * This method would get the current url from $_SERVER
     * @return string
     */
    private function getUrlFromServer() : string
    {
        // build url
        $url = '';
        
        // get from http host
        $host = $this->tryHttpHost();

        // build domain
        $domain = $host['scheme'] . $host['host'];

        // set app.url in environment
        if (function_exists('env_set')) env_set('bootstrap/app.url', $domain);

        // return string
        return $domain;
    }


    /**
     * @method URLInterface tryHttpHost
     * 
     * This method tries fetching the url from $_SERVER['HTTP_HOST']
     * It will return a host url with request scheme if HTTP_HOST is not null
     * @return array
     */
    private function tryHttpHost() : array
    {
        if (isset($_SERVER['SERVER_SOFTWARE'])) :

            // get software 
            $software = $_SERVER['SERVER_SOFTWARE'];

            // get http host from _SERVER or default to empty string
            $httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : [];

            // get request scheme from HTTP_X_FORWARDED_PROTO
            $scheme = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : null;
                
            // if null, check REQUEST_SCHEME
            $scheme = $scheme == null ? (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : $this->defaultScheme) : $this->defaultScheme;

            // check php development server
            if (stripos($software, 'Development Server') === false) :

                // get PHP_SELF
                $self = $_SERVER['PHP_SELF'];

                // remove base name
                $self = rtrim($self, basename($self));

                // concat string
                $httpHost = $httpHost . $self;

            endif;

        else:

            $scheme = 'http://';
            $httpHost = 'localhost';

        endif;

        // build httpHost
        $httpHost = ['scheme' => $scheme . '://', 'host' => $httpHost];

        // return http host
        return $httpHost;
    }

    /**
     * @method URLInterface getRootDirectoryForUrlIfLocal
     * @param array $requestArray
     * This returns the root folder if found
     * @return string
     */
    private function getRootDirectoryForUrlIfLocal(array $requestArray)
    {
        // prepare request string
        $requestString = '';

        // check if host was passed
        if (isset($requestArray['host'])) :
        
            // get host
            $getHost = $requestArray['host'];

            // get current working directory from DOCUMENT_ROOT
            // remove trailing forward slash (/)
            // get root from script
            $root = basename(ltrim($_SERVER['DOCUMENT_ROOT'], '/')) . '/';

            // not running from dev server
            if (isset($_SERVER['SERVER_SOFTWARE'])) :

                if (stripos($_SERVER['SERVER_SOFTWARE'], 'development')) $root = '';

            endif;

            // build request string 
            $requestString = $requestArray['scheme'] . $getHost . '/' . $root;
        
        endif;

        // return request string
        return $requestString;
    }
}