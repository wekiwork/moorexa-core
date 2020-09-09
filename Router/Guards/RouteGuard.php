<?php
namespace Lightroom\Router\Guards;

use Lightroom\Router\Interfaces\RouteGuardInterface;
use function Lightroom\Requests\Functions\{session};

/**
 * @package Route Guard
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait RouteGuard
{
    /**
     * @var array $incomingUrl
     */
    private $incomingUrl = [];

    /**
     * @var RouteGuard FIRST_PARAM
     */
    private $FIRST_PARAM = 0;

    /**
     * @var RouteGuard SECOND_PARAM
     */
    private $SECOND_PARAM = 1;

    /**
     * @var RouteGuard THIRD_PARAM
     */
    private $THIRD_PARAM = 2;

    /**
     * @method RouteGuardInterface setIncomingUrl
     * @param array $incomingUrl
     * @return void
     */
    public function setIncomingUrl(array $incomingUrl) : void
    {
        $this->incomingUrl = $incomingUrl;
    }

    /**
     * @method RouteGuardInterface setView
     * @param string $view
     * @return void
     */
    public function setView(string $view) : void 
    {
        $this->incomingUrl[(int) $this->SECOND_PARAM] = $view;
    }

    /**
     * @method RouteGuardInterface setController
     * @param string $controller
     * @return void
     */
    public function setController(string $controller) : void 
    {
        $this->incomingUrl[(int) $this->FIRST_PARAM] = $controller;
    }

    /**
     * @method RouteGuardInterface getView
     * @return string
     */
    public function getView() : string 
    {
        return isset($this->incomingUrl[(int) $this->SECOND_PARAM]) ? $this->incomingUrl[(int) $this->SECOND_PARAM] : '';
    }

    /**
     * @method RouteGuardInterface getController
     * @return string
     */
    public function getController() : string 
    {
        return isset($this->incomingUrl[(int) $this->FIRST_PARAM]) ? $this->incomingUrl[(int) $this->FIRST_PARAM] : '';
    }

    /**
     * @method RouteGuardInterface getIncomingUrl
     * @return array
     */
    public function getIncomingUrl() : array 
    {
        return $this->incomingUrl;
    }

    /**
     * @method RouteGuard getArguments
     * @return array
     */
    public function getArguments() : array 
    {
        // @var array $arguments
        $arguments = $this->incomingUrl;

        // extract from index 2
        $arguments = array_splice($arguments, (int) $this->THIRD_PARAM);

        // return array
        return $arguments;
    }

    /**
     * @method RouteGuard setArguments
     * @param mixed $arguments
     * @return void
     */
    public function setArguments(...$arguments) : void 
    {
        // try update arguments
        if (isset($arguments[0])) : 

            // check if first argument is an array and update $arguments
            if (is_array($arguments[0])) $arguments = $arguments[0];

        endif;

        // @var array $incomingUrl
        $incomingUrl = $this->incomingUrl;

        // get the first 2 
        $incomingUrl = array_splice($incomingUrl, (int)$this->FIRST_PARAM, (int) $this->THIRD_PARAM);
        
        // merge arguments with incoming url
        $this->incomingUrl = array_merge($incomingUrl, $arguments);
    }

    /**
     * @method RouteGuard redirectPath
     * @return mixed
     */
    public function redirectPath()
    {
        $this->redirect($this->getRedirectPath());
    }

    /**
     * @method RouteGuard redirect
     * @param string $path (optional)
     * @param array $arguments
     * @return mixed
     */
    public function redirect(string $path = '', array $arguments = []) 
    {
        // get redirect url
        if ($path != '') :

            http_response_code(301);
            
            // not external link
            if (!preg_match("/(:\/\/)/", $path)) :

                // get query
                $query = isset($arguments['query']) && is_array($arguments['query']) ? '?' . http_build_query($arguments['query']) : '';

                // get redirect data
                $data = [];

                // check query
                if (strlen($query) > 3) :

                    // check for data in arguments
                    $data = isset($arguments['data']) && is_array($arguments['data']) ? $arguments['data'] : [];

                else:

                    // data would be arguments here
                    $data = $arguments;

                endif;


                // get current request
                $currentRequest = ltrim($_SERVER['REQUEST_URI'], '/');

                // add query to path
                $pathWithQuery = $path . $query;

                // redirect if pathWithQuery is not equivalent to the current request
                if ($pathWithQuery != $currentRequest) :

                    // export data
                    if (count($data) > 0) :

                        // get redirect data
                        $redirectData = session()->get('redirect.data');

                        // create array if not found
                        if (!is_array($redirectData)) $redirectData = [];

                        // lets add path
                        $redirectData[$pathWithQuery] = $data;

                        // set redirect data
                        session()->set('redirect.data', $redirectData);

                    endif;

                    // perform redirection
                    header('location: '. func()->url($pathWithQuery), true, 301); exit;

                endif;

            else:

                // build query
                $query = http_build_query($arguments);

                // check length
                $query = strlen($query) > 1 ? '?' . $query : $query;

                // redirect to external link
                header('location: ' . $path . $query, true, 301); exit;

            endif;

        else:   

            // return object
            return new class()
            {
                /**
                 * @var array $exported
                 */
                private $exported = [];

                // load exported data
                public function __construct()
                {
                    // get current request
                    $currentRequest = ltrim($_SERVER['REQUEST_URI'], '/');

                    if (session()->has('redirect.data')) :

                        // @var array $data
                        $data = session()->get('redirect.data');

                        // check for exported data for current request
                        if (isset($data[$currentRequest])) :

                            // set
                            $this->exported = $data[$currentRequest];

                            // clean up
                            unset($data[$currentRequest]);

                            // set session again
                            session()->set('redirect.data', $data);

                        endif;

                    endif;
                }

                /**
                 * @method Common data
                 * @return array
                 */
                public function data() : array 
                {
                    return $this->exported;
                }

                /**
                 * @method Common has
                 * @param array $arguments
                 * @return bool
                 */
                public function has(...$arguments) : bool 
                {
                    // @var int $found
                    $found = 0;

                    // @var bool $has 
                    $has = false;

                    // check now
                    foreach ($arguments as $name) if (isset($this->exported[$name])) $found++;

                    //compare found
                    if (count($arguments) == $found) $has = true;

                    // return bool
                    return $has;
                }

                /**
                 * @method Common get
                 * @return mixed
                 */
                public function get(string $name) 
                {
                    return isset($this->exported[$name]) ? $this->exported[$name] : null;
                }

                /**
                 * @method Common __get
                 * @param string $name
                 * @return mixed
                 */
                public function __get(string $name) 
                {
                    // return value
                    return $this->get($name);
                }
            };

        endif;
    }
}