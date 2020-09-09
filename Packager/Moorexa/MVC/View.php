<?php
namespace Lightroom\Packager\Moorexa\MVC;

use Exception;
use Lightroom\Adapter\ClassManager;
use Lightroom\Packager\Moorexa\{
    Helpers\Assets, MVC\Helpers\ControllerLoader
};
use Lightroom\Exceptions\ClassNotFound;

/**
 * @package Moorexa View Handler
 * @author Amadi Ifeanyi <amadiify.com>
 */
class View extends ControllerLoader
{
    use Helpers\ViewPackage;

    /**
     * @var array $javascripts
     */
    public  static  $javascripts = [];

    /**
     * @var array $cssfiles
     */
    public  static  $cssfiles = [];

    /**
     * @var array $liveStaticFiles
     */
    public $liveStaticFiles = [];

    /**
    * @var array $cssArray
    */
    public $cssArray = [];

    /**
     * @var array $liveStatic
     */
    private static $liveStatic = ['css' => [], 'js' => []];

    /**
     * @var array $liveStaticLoaded 
     */
    public static $liveStaticLoaded = ['css' => [], 'js' => []];

    /**
     * @var object $static
     */
    private static $static = null;

    /**
     * @var object $bundle
     */
    private $bundle = null;

    /**
     * @var array
     */
    private $loadCss = [];

    /**
     * @var array
     */
    private $loadJs = [];

    /**
     * @var string $session_token
     */
    public $session_token = null;

    /**
     * @method View loadStatic
     * @param Object $static
     * @return void
     */
    public static function loadStatic($static) : void
    {
        self::$static = (object) $static;
    }

    /**
     * @method View loadBundle
     * @return View
     * @throws Exception
     */
    public function loadBundle() : View
    {
        if (is_null($this->bundle)) :
        
            // @var object $loadStatic
            $loadStatic = self::$static;

            $this->bundle = $loadStatic;

            if (is_null($loadStatic)) :
            
                $path = DISTRIBUTION_BASE_PATH . '/loadStatic.json';

                if (file_exists($path)) :
                
                    $content = file_get_contents($path);
                    $json = json_decode(trim($content));

                    // update bundle
                    $this->bundle = is_null($json) ? (object) ['scripts' => [], 'stylesheet' => []] : $json;

                endif;

            endif;

            
            // ok let's load bundle
            $cssBundle = self::config('static.css', []);

            if (count($cssBundle) > 0) $this->bundle->stylesheet = $cssBundle;
            

            // check for javascript
            $jsBundle = self::config('static.js', []);

            if (count($jsBundle) > 0) $this->bundle->scripts = $jsBundle;

        endif;

        return $this;
    }

    /**
     * @method View unpackCss
     * @param array $Css
     * @return array
     * @throws ClassNotFound
     */
    public function unpackCss(array $Css = []) : array
    {
        if (count($Css) == 0) $Css = $this->bundle->stylesheet;

        // @var Assets $assets
        $assets = ClassManager::singleton(Assets::class);

        $bundle = 'stylesheet@bundle';
        $offloadCss = true;

        // check bundle
        if (isset($this->bundle->{$bundle})) :
        
            $bundles = $this->bundle->{$bundle};

            if (count($bundles) > 0) :
            
                $offloadCss = false;

                foreach ($bundles as $bundle) self::$cssfiles[] = $assets->css($bundle);

            endif;

        endif;

        if ($offloadCss) :
        
            if(isset($Css[0]) && !empty($Css[0])) :
            
                foreach($Css as $key => $val) :
                
                    $val = trim($val);

                    if (file_exists($val)) :
                    
                        self::$cssfiles[] = $val;
                    
                    else:
                    
                        if(preg_match("/^[http|https]+[:\/\/]/", $val) == true) :
                        
                            // lets get the filename
                            self::$cssfiles[] = $val;
                        
                        else:
                        
                            $path = $assets->css($val);

                            if ($path != "") self::$cssfiles[] = $path;

                        endif;

                    endif;

                endforeach;

            endif;

        endif;

        return array_merge(self::$cssfiles, self::$liveStatic['css']);
    }

    /**
     * @method View unpackJavascripts
     * @param array $Js
     * @return array
     * @throws ClassNotFound
     */
    public function unpackJavascripts(array $Js = []) : array
    {
        if (count($Js) == 0) $Js = $this->bundle->scripts;

        // @var Assets $assets
        $assets = ClassManager::singleton(Assets::class);

        // @var string $bundle
        $bundle = 'scripts@bundle';

        // @var bool $unpackJs
        $unpackJs = true;

        // check bundle 
        if (isset($this->bundle->{$bundle})) :
        
            $bundles = $this->bundle->{$bundle};

            if (count($bundles) > 0) :
            
                $unpackJs = false;

                foreach ($bundles as $bundle) self::$javascripts[] = $assets->js($bundle);

            endif;

        endif;

        if ($unpackJs) :

            if (isset($Js[0]) && !empty($Js[0])) :
            
                foreach($Js as $key => $val) :
                
                    $val = trim($val);

                    if (file_exists($val)) :
                    
                        self::$javascripts[] = func()->url($val);
                    
                    else:
                    
                        if(preg_match("/^[http|https]+[:\/\/]/", $val) == true) :
                        
                            // lets get the filename
                            $filename = explode("/", $val);
                            $filename = end($filename);

                            self::$javascripts[] = $val;
                        
                        else :

                            $path = $assets->js($val);

                            if ($path != '') self::$javascripts[] = $path;

                        endif;

                    endif;

                endforeach;

            endif;

        endif;

        return array_merge(self::$javascripts, self::$liveStatic['js']);
    }

    /**
     * @method View unpack
     * @return void
     */
    public function unpack() : void
    {
        // unpack css
        switch (count(self::$cssfiles) > 0) :
        
            case true:
                
                $this->loadCss = self::$cssfiles;

                // clean
                self::$cssfiles = [];

            break;

        endswitch;

        // unpack javascript
        switch (count(self::$javascripts) > 0) :
        
            case true:
                
                $this->loadJs = self::$javascripts;

                // clean
                self::$javascripts = [];

            break;
        
        endswitch;

    }

    /**
     * @method View requireJs
     * @param array ...$arguments
     * @return View
     */
    public function requireJs(...$arguments) : View 
    {
        foreach ($arguments as $javascript) :

            if (preg_match('/^(http)/', $javascript)) :
                // add javascript
                self::$liveStaticLoaded['js'][] = $javascript;
            else:
                // check if file exits
                if (file_exists($javascript)) :

                    // add javascript
                    self::$liveStaticLoaded['js'][] = func()->url($javascript);
                else:

                    // add javascript
                    self::$liveStatic['js'][] = $javascript;
                endif;
            endif;

        endforeach;

        // return instance
        return $this;
    }

    /**
     * @method View requireCss
     * @param array ...$arguments
     * @return View
     */
    public function requireCss(...$arguments) : View 
    {
        foreach ($arguments as $css) :

            if (preg_match('/^(http)/', $css)) :
                // add css
                self::$liveStaticLoaded['css'][] = $css;
            else:

                // check if file exits
                if (file_exists($css)) :

                    // add css
                    self::$liveStaticLoaded['css'][] = func()->url($css);
                else:

                    // add css
                    self::$liveStatic['css'][] = $css;
                endif;

            endif;

        endforeach;

        // return instance
        return $this;
    }

    // unpack require
    private function unpackRequire($require, &$data)
    {
        $top = [];
        $bottom = [];
        $before = [];

        // push to top and bottom
        array_walk($require, function($req) use (&$top, &$bottom, &$before){
            $pos = $req['position'];
            if ($pos == 'top')
            {
                $top[] = $req['file'];
            }
            elseif ($pos == 'bottom')
            {
                $bottom[] = $req['file'];
            }
            else
            {
                $pos = trim($pos);
                if (preg_match('/^(before)\s+([\S]*)/', $pos, $match))
                {
                    $before[] = [$match[2] => $req['file']];
                }
                else
                {
                    $bottom[] = $req['file'];
                }
            }
        });

        // stack on top
        if (count($top) > 0)
        {
            $len = count($top)-1;

            for ($i=$len; $i != -1; $i--)
            {
                array_unshift($data, $top[$i]);
            }
        }

        // stack below
        if (count($bottom) > 0)
        {
            foreach ($bottom as $i => $fl)
            {
                array_push($data, $fl);
            }
        }

        // stack before
        if (count($before) > 0)
        {
            foreach ($before as $i => $pathData)
            {
                $keys = array_keys($pathData);

                // get position in data
                foreach ($data as $index => $line)
                {
                    // get base
                    $base = basename($line);
                    // quote
                    $quote = preg_quote($keys[0], '/');
                    if (preg_match("/($quote)/i", $base) || ($keys[0] == $line))
                    {
                        array_splice($data,$index,1,[$pathData[$keys[0]],$line]);
                        break;
                    }
                }
            }
        }

        // clean
        $top = null;
        $bottom = null;
        $before = null;
    }
}