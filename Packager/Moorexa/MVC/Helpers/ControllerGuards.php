<?php
namespace Lightroom\Packager\Moorexa\MVC\Helpers;

use Lightroom\Router\{
    Guards\RouteGuard, Interfaces\GuardInterface,
    Interfaces\RouteGuardInterface
};
/**
 * @package Guards ControllerGuards
 * @author Amadi Ifeanyi <amadiify.com>
 */
class ControllerGuards implements GuardInterface, RouteGuardInterface
{
    use RouteGuard;

    /**
     * @var ControllerGuards $instance
     */
    private static $instance;

    /**
     * @var ControllerGuards $redirectPath
     */
    private $redirectPath = '';

    /**
     * @method GuardInterface guardInit
     * @return void
     * 
     * This method would be called when guard has been initialized. 
     */
    public function guardInit() : void
    {
        // set instance
        self::$instance =& $this;

        // include global route file
        include_once get_path(func()->const('services'), '/guards.php');
    }

    /**
     * @method RouteGuardInterface setRedirectPath
     * @param string $path
     * @return void This method sets a redirect path
     *
     * This method sets a redirect path
     */
    public function setRedirectPath(string $path) : void 
    {
        $this->redirectPath = $path;
    }

    /**
     * @method ControllerGuards getInstance
     * @return ControllerGuards
     */
    public static function getInstance() : ControllerGuards
    {
        return self::$instance;
    }

    /**
     * @method RouteGuardInterface getRedirectPath 
     * @return string
     * 
     * This method returns a redirect path
     **/
    public function getRedirectPath() : string 
    {
        return $this->redirectPath;
    }
}