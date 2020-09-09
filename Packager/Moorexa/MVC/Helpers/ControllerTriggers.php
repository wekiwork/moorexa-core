<?php
namespace Lightroom\Packager\Moorexa\MVC\Helpers;

use Exception;
use Lightroom\Packager\Moorexa\MVC\Controller;
use function Lightroom\Templates\Functions\view;
use Lightroom\Packager\Moorexa\Interfaces\{
    ModelInterface, ControllerProviderInterface,
    ViewProviderInterface
};
use Lightroom\Packager\Moorexa\Helpers\URL;
/**
 * @package Controller triggers
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait ControllerTriggers
{
    /**
     * @method ControllerTriggers ___getView
     * @return mixed
     */
    public function ___getView()
    {
        return new class()
        {
            /**
             * @method ControllerTriggers __get
             * @param string $method
             * @return mixed
             */
            public function __get(string $method)
            {
                // provider
                if ($method == 'provider') return Controller::getInstance()->___getViewProvider();

                return null;
            }
            
            /**
             * @method ControllerTriggers __call
             * @param string $method
             * @param array $arguments
             * @return mixed
             */
            public function __call(string $method, array $arguments) 
            {
                switch ($method) :
                    // render, redirect and json
                    case 'render':
                        // load controller variables
                        Controller::getInstance()->loadControllerVariables();

                    case 'redirect':
                    case 'redir':
                    case 'json':
                        return call_user_func_array('Lightroom\Templates\Functions\\' . ($method == 'redir' ? 'redirect' : $method) , $arguments);

                    default:
                        return call_user_func_array([view(), $method], $arguments);

                endswitch;
            }
        };
    }

    /**
     * @method ControllerTriggers ___getModel
     * @return ModelInterface
     * @throws Exception
     */
    public function ___getModel() : ModelInterface
    {
        // @var array $incomingUrl
        $incomingUrl = URL::getIncomingUri();

        if (!isset(self::$controllerSystemVariables['viewModel'])) 
            throw new Exception('No View model loaded for controller view #'. $incomingUrl[1]);

        // return ModelInterface
        return self::$controllerSystemVariables['viewModel'];
    }

    /**
     * @method ControllerTriggers ___getProvider
     * @return mixed
     * @throws Exception
     */
    public function ___getProvider()
    {
        // check for view provider
        if (isset(self::$controllerSystemVariables['viewProvider'])) return $this->___getViewProvider();

        // return controller provider
        return $this->___getDefaultProvider();
    }

    /**
     * @method ControllerTriggers ___getDefaultProvider
     * @return mixed
     * @throws Exception
     */
    public function ___getDefaultProvider()
    {
        if (!isset(self::$controllerSystemVariables['controllerProvider'])) 
            throw new Exception('No Controller provider loaded for controller #'. get_class(self::getInstance()));

        // return controller provider
        return self::$controllerSystemVariables['controllerProvider'];
    }

    /**
     * @method ControllerTriggers ___getViewProvider
     * @return mixed
     * @throws Exception
     */
    public function ___getViewProvider() : ViewProviderInterface
    {
        // @var array $incomingUrl
        $incomingUrl = URL::getIncomingUri();

        if (!isset(self::$controllerSystemVariables['viewProvider'])) 
            throw new Exception('No View provider loaded for controller view #'. $incomingUrl[1]);

        // return view provider
        return self::$controllerSystemVariables['viewProvider'];
    }
}