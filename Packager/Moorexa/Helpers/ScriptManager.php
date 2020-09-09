<?php 
namespace Lightroom\Packager\Moorexa\Helpers;
/**
 * @package Script Manager
 * @author Amadi Ifeanyi <amadiify.com>
 */
class ScriptManager
{
    /**
     * @method ScriptManager constructor
     * This method loads the script file
     */
    public function __construct()
    {
        // @var string $scriptFile
        $scriptFile = func()->const('services') . '/scripts.php';

        // if it exists then load
        if (file_exists($scriptFile)) include_once $scriptFile;
    }

    /**
     * @method ScriptManager execute
     * @param array $scripts
     * @return void
     */
    public static function execute(array $scripts) : void 
    {
        if (count($scripts) > 0) :

            // load scripts
            foreach ($scripts as $method => $script) :

                if (is_string($method) && is_string($script)) :

                    // load class
                    call_user_func([$script, $method]);

                endif;

            endforeach;

        endif;
    }
}