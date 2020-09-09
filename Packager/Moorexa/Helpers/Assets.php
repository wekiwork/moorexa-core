<?php
namespace Lightroom\Packager\Moorexa\Helpers;

use Exception, Closure;
use Lightroom\Packager\Moorexa\MVC\View;
use Lightroom\Packager\Moorexa\MVC\Helpers\ControllerLoader as Controller;
/**
 * @package Assets Manager
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Assets
{
	// folder
	private $folder = null;
	private $build = [];
	private $resize = [];
	private $output = null;
	private $dom = null;
	private $static = '';
	private $image_path = '';
	private $css_path = '';
	private $js_path = '';
	public static $jsScripts = [];
	public static $jsLoadConfig = [];
	public static $changePosition = ['css' => [], 'js' => []];
	public static $phpVarsData = [];
	private static $subscribers = [];

    public function __get($name)
	{
		$this->static = env('bootstrap', 'staticurl');

		$this->folder = $name;

		return $this;
	}

	/**
	 * @method Assets config
	 * @param array $data 
	 * @return void
	 * 
	 * Enable access to private properties
	 */
	public function config(array $data) : void
	{
		foreach ($data as $key => $val) :
			
			if (property_exists($this, $key)) $this->{$key} = $val;
		
		endforeach;
	}

    /**
     * @method Assets image
     * @param string $file
     * @return string
     *
     * Load an image file path.
     * @throws Exception
     */
	public function image(string $file) : string
	{
		// fix window file issue
		$file = str_replace('\\', '/', $file);

		if ($this->getFileIfCache($file, $cache, $json)) :
		
			return $cache;
		
		else:
			
			// load subscriber
			$file = $this->loadSubsribers('image', $file);
		
			// set the image path
			$this->image_path = $this->image_path == '' ? func()->const('image') . '/' : $this->image_path;

			// @var array $incomingUrl
			$incomingUrl = URL::getIncomingUri();

			$cache = func()->const('assets') . '/assets.paths.json';
			$size = null;
			$fileNameCopy = $file;
			$fileNoUpdate = $file;

			if (strpos($file,'@') !== false) :
			
				$size = substr($file, strpos($file,'@')+1);
				$file = substr($file, 0, strpos($file,'@'));
				$size = explode(':',$size);
				$fileNameCopy = $file;

			endif;

			$other = null;

			if (strpos($file, '::') !== false) :
			
				$folder = substr($file, 0, strpos($file, '::'));
				$file = substr($file, strpos($file, '::'));
				$file = preg_replace('/[:]/','',$file);
				$folder = preg_replace('/[:]/','',$folder);
				$this->folder = $folder;
				$folder = null;

			endif;
			
			$filePassed = $file;
			$file = explode('/', $file);
			$fileBaseName = end($file);
			array_pop($file);
			$extra = implode('/', $file);
			$file = $fileBaseName;

			$controller = $incomingUrl[0];

			if ($this->static == '') :
			
				// failed! so we check
				$newPath = null;

				$parse = parse_url($filePassed);
				
				if (isset($parse['scheme'])) :
				
					$newPath = $filePassed;
					$json[$fileNoUpdate] = $filePassed;
				
				else:
				
					// get image path
					$getImage = function($file) use ($fileNameCopy, &$json, $fileNoUpdate, $size)
					{
						$dir = $this->image_path;
						$getPath = $dir . $file;

						$parse = parse_url($fileNameCopy);

						if (file_exists($file)) :
						
							$scan = $file;
						
						elseif (file_exists($fileNameCopy) || isset($parse['scheme'])) :
						
							$scan = $fileNameCopy;
						
						elseif (file_exists($getPath)) :
						
							$scan = $getPath;
						
						else:
						
							$scan = func()->deepScan($dir, $file);

						endif;

						if ($scan != '') :

							$path = func()->url($scan);

							$json[$fileNoUpdate] = $scan;

							return $path;

						endif;

						// static url added ?
						if (env('bootstrap', 'static_url') != '') :
						
							// return image path.
							return  env('bootstrap', 'static_url') . preg_replace('/^(.\/)/', '', $fileNoUpdate);

						endif;

						// return string
						return func()->url(func()->deepscan(func()->const('image') . '/', 'no-image-available.png'));
					};
					
					switch (is_dir($this->image_path . $controller)) :
					
						case true:
							$dir = $this->image_path . $controller . '/';
							$getPath = $dir . $filePassed;

							if (file_exists($getPath)) :
							
								$scan = $getPath;
							
							else:
							
								$scan = func()->deepScan($dir, $filePassed);
								
							endif;

							if ($scan == '') :
							
								$newPath = $getImage($filePassed);
							
							else:
							
								$path = func()->url($scan);

								$json[$fileNoUpdate] = $scan;
								$newPath = $path;

							endif;

						break;

						case false:

							// @var string $fromStatic
							$fromStatic = Controller::getControllerPath($controller) . '/' . $filePassed;

							// check if file exists
							if (file_exists($fromStatic)) $filePassed = $fromStatic;

							$newPath = $getImage($filePassed);

						break;
					
					endswitch;

				endif;

				// save json
				if (count($json) > 0) :
				
					if (is_writable($cache)) :
					
						file_put_contents($cache, json_encode($json, JSON_PRETTY_PRINT));

					endif;

				endif;


				// return string 
				return $newPath;
			
			endif;
			

			// return string
			return rtrim($this->static, '/') . '/' . $this->folder . '/' . $file;
			
		endif;
	}

    /**
     * @method Assets css
     * @param string $file
     * @return string
     *
     * Load a css file path.
     * @throws Exception
     */
	public function css(string $file) : string 
	{
		// fix window file issue
		$file = str_replace('\\', '/', $file);

		if ($this->getFileIfCache($file, $cache, $json)) :
		
			return $cache;
		
		else:
		
			// load subscriber
			$file = $this->loadSubsribers('css', $file);

			// set the css path
			$this->css_path = $this->css_path == '' ? func()->const('css') . '/' : $this->css_path;

			// @var array $incomingUrl
			$incomingUrl = URL::getIncomingUri();

			$queryInPath = '';

			if (strpos($file, '?') !== false) :
			
				$end = strpos($file, '?');
				$fileName = substr($file, 0, $end);
				$queryInPath = substr($file, $end);
				$file = $fileName;

			endif;

			$cache = func()->const('assets') . '/assets.paths.json';
			$fileNameCopy = $file;
			$fileNoUpdate = $file . $queryInPath;
			$controller = $incomingUrl[0];

			// get css path
			$getCss = function($file) use ($fileNameCopy, &$json, $fileNoUpdate, $queryInPath) : string
			{
				$dir = $this->css_path;
				$getPath = $dir . $file;
				$parse = parse_url($fileNameCopy);

				if (file_exists($file)) :
				
					$scan = $file;
				
				elseif (file_exists($fileNameCopy) || isset($parse['scheme'])) :
				
					$scan = $fileNameCopy;
				
				elseif (file_exists($getPath)) :
				
					$scan = $getPath;
				
				elseif (file_exists(func()->const('assets') . '/' . $file)) :
				
					$scan = func()->const('assets') . '/' . $file;
				
				else:
				
					$scan = func()->deepscan($dir, $file);

				endif;

				if ($scan != '') :

					$json[$fileNoUpdate] = $scan . $queryInPath;

				endif;

				return $scan;
			};

			/**@var string $newPath*/
            $newPath = '';

			switch (is_dir($this->css_path . $controller)) :
			
				case true:

					$dir = $this->css_path . $controller . '/';
					$getPath = $dir . $file;

					if (file_exists($getPath)) :
					
						$scan = $getPath;
					
					else:
					
						$scan = func()->deepscan($dir, $file);

					endif;

					if ($scan == '') :
					
						$newPath = $getCss($file);
					
					else:
					
						$json[$fileNoUpdate] = $scan . $queryInPath;

						$newPath = $scan;

					endif;

				break;

				case false:

					// @var string $fromStatic
					$fromStatic = Controller::getControllerPath($controller) . '/' . $file;

					if (file_exists($fromStatic)) $file = $fromStatic;

					$newPath = $getCss($file);

				break;
				
			endswitch;

			// save json
			if (count($json) > 0) :
			
				if (is_writable($cache)) :
				
					file_put_contents($cache, json_encode($json, JSON_PRETTY_PRINT));

				endif;

			endif;

			// return string
			return $newPath . $queryInPath;
		
		endif;
	}

	/**
	 * @method Assets staticInclude
	 * @param string $file 
	 * @return void
	 * 
	 * Include static file in dom, interpolate php variables and functions also
	 */
	public function staticInclude(string $file) : void
	{
		if (file_exists($file)) :
		
			ob_start();
			include_once($file);
			$content = ob_get_contents();
			ob_end_clean();

			// get extension
			$extension = strtolower(func()->extension($file));

			switch($extension) :
			
				case 'css':
					echo '<style type="text/css">'.$content.'</style>';
				break;

				case 'js':
					echo '<script>'.$content.'</script>';
				break;

			endswitch;

		endif;
	}

    /**
     * @method Assets js
     * @param string $file
     * @return string
     *
     * Load a javascript file path.
     * @throws Exception
     */
	public function js(string $file) : string
	{
		// fix window file issue
		$file = str_replace('\\', '/', $file);

		if ($file == 'php-vars.js') :
		
			// would import all data available in Assets::$phpVarsData
			return $file;

		endif;

		if ($this->getFileIfCache($file, $cache, $json)) :
		
			return $cache;
		
		else:
			
			// load subscriber
			$file = $this->loadSubsribers('js', $file);

			// set the js path
			$this->js_path = $this->js_path == '' ? func()->const('js') . '/' : $this->js_path;

			// @var array $incomingUrl
			$incomingUrl = URL::getIncomingUri();

			$queryInPath = '';

			if (strpos($file, '?') !== false) :
			
				$end = strpos($file, '?');
				$fileName = substr($file, 0, $end);
				$queryInPath = substr($file, $end);
				$file = $fileName;

			endif;

			$cache = func()->const('assets') . '/assets.paths.json';
			$fileNameCopy = $file;
			$fileNoUpdate = $file . $queryInPath;
			$controller = $incomingUrl[0];

			// get js path
			$getJs = function($file) use ($fileNameCopy, &$json, $fileNoUpdate, $queryInPath)
			{
				$dir = $this->js_path;
				$getPath = $dir . $file;

				$parse = parse_url($fileNameCopy);

				if (file_exists($file)) :
				
					$scan = $file;
				
				elseif (file_exists($fileNameCopy) || isset($parse['scheme'])) :
				
					$scan = $fileNameCopy;
				
				elseif (file_exists($getPath)) :
				
					$scan = $getPath;
				
				elseif (file_exists(func()->const('assets') . '/' . $file)) :
				
					$scan = func()->const('assets') . '/' . $file;
				
				else:
				
					$scan = func()->deepscan($dir, $file);

				endif;

				if ($scan != '') :

					$json[$fileNoUpdate] = $scan . $queryInPath;

				endif;

				return $scan;
			};

			switch (is_dir($this->js_path . $controller)) :
			
				case true:

					$dir = $this->js_path . $controller . '/';
					$getPath = $dir . $file;

					if (file_exists($getPath)) :
					
						$scan = $getPath;
					
					else:
					
						$scan = func()->deepscan($dir, $file);

					endif;

					if ($scan == '') :
					
						$newpath = $getJs($file);
					
					else:
					
						$json[$fileNoUpdate] = $scan . $queryInPath;

						$newpath = $scan;

					endif;

				break;

				case false:
					
					// @var string $fromStatic
					$fromStatic = Controller::getControllerPath($controller) . '/' . $file;

					if (file_exists($fromStatic)) { $file = $fromStatic; }

					$newpath = $getJs($file);

				break;

			endswitch;

			// save json
			if (count($json) > 0) :
			
				if (is_writable($cache)) :
				
					file_put_contents($cache, json_encode($json, JSON_PRETTY_PRINT));

				endif;

			endif;

			return $newpath . $queryInPath;

		endif;
	}

	/**
	 * @method Assets media
	 * @param string $file 
	 * @return string
	 * 
	 * Load a media file path.
	 */
	public function media(string $file) : string 
	{
		// fix window file issue
		$file = str_replace('\\', '/', $file);
		
		if ($this->getFileIfCache($file, $cache, $json)) :
		
			return $cache;
		
		else:
		
			// load subscriber
			$file = $this->loadSubsribers('media', $file);

			// @var array $incomingUrl
			$incomingUrl = URL::getIncomingUri();

			$cache = func()->const('assets') . 'assets.paths.json';
			$filecopy = $file;
			$fileNoUpdate = $file;
			$controller = $incomingUrl[0];

			// get media path
			$getMedia = function($file) use ($filecopy, &$json, $fileNoUpdate)
			{
				$dir = func()->const('media');

				$getPath = $dir . $file;

				$parse = parse_url($filecopy);

				if (file_exists($filecopy) || isset($parse['scheme'])) :
				
					$scan = $filecopy;
				
				elseif (file_exists($getPath)) :
				
					$scan = $getPath;
				
				else:
				
					$scan = func()->deepscan($dir, $file);

				endif;

				if ($scan != '') :
				
					$json[$fileNoUpdate] = $scan;

				endif;

				return $scan;
			};

			switch (is_dir(func()->const('media') . '/' . $controller)) :
			
				case true:

					$dir = func()->const('media') . '/' . $controller . '/';
					$getPath = $dir . $file;

					if (file_exists($getPath)) :
					
						$scan = $getPath;
					
					else:
					
						$scan = func()->deepscan($dir, $file);

					endif;

					if ($scan == '') :
					
						$newpath = $getMedia($file);

					else:
					
						$json[$fileNoUpdate] = $scan;

						$newpath = $scan;

					endif;

				break;

				case false:

					$newpath = $getMedia($file);

				break;

			endswitch;

			// save json
			if (count($json) > 0) :
			
				if (is_writable($cache)) :
				
					file_put_contents($cache, json_encode($json, JSON_PRETTY_PRINT));

				endif;

			endif;

			// return string 
			return $newpath;

		endif;
	}

	/**
	 * @method Assets loadCss
	 * @param array $cssFiles
	 * @return string
	 * 
	 * This method would load a packed css files. 
	 */
	public function loadCss(array $cssFiles) : string 
	{
		$css = [];

		// check if a css position has been changed
		$this->changePositionIfChanged('css', $cssFiles);

		// merge css file
		$cssFiles = array_merge($cssFiles, View::$liveStaticLoaded['css']);

		// get static url
		$static_url = env('bootstrap', 'static_url');

		// get url
		$url = $static_url != '' ? $static_url . '/' : func()->url();

		// iterate
		foreach ($cssFiles as $val) :

			// has request
			$parse = parse_url($val);

			// get original
			$original = $val;

			if (!isset($parse['scheme'])) $val = $url . $val;

			// load subscriber
			$css[] = $this->loadSubsribers('loadCss', $original, '<link rel="stylesheet" type="text/css" href="'.$val.'"/>');

		endforeach;

		// return css
		return implode("\n\t", $css);
	}

    /**
     * @method Assets loadJs
     * @param array $jsFiles
     * @return string
     *
     * This method would load a packed javascript files.
     * @throws Exception
     */
	public function loadJs(array $jsFiles) : string 
	{
		$js = [];

		// add php-vars.js 
		if (count(self::$phpVarsData) > 0) array_unshift($jsFiles, 'php-vars.js');

		// check if a javascript position has been changed
		$this->changePositionIfChanged('js', $jsFiles);

		// merge js file
		$jsFiles = array_merge($jsFiles, View::$liveStaticLoaded['js']);

		// get static url
		$static_url = env('bootstrap', 'static_url');

		// get url
		$url = $static_url != '' ? $static_url . '/' : func()->url();

		// iterate
		foreach ($jsFiles as $val) :

			// has request
			$parse = parse_url($val);

			// original file
			$original = $val;

			// add url
			if (!isset($parse['scheme'])) $val = $url . $val;

			// @var string $type
			$type = 'text/deffered';

			// @var string $base 
			$base = basename($val);

			if (isset(self::$jsLoadConfig[$base])) :
			
				// @var array $config
				$config = self::$jsLoadConfig[$base];

				// update $type
				$type = 'text/deffered';

				// update $type
				if (isset($config['deffer']) && !$config['deffer']) $type = 'text/javascript';

			endif;

			if ($base != 'php-vars.js') :
			
				// load subscriber
				$js[] = $this->loadSubsribers('loadJs', $original, '<script type="'.$type.'" src="'.$val.'"></script>');
			
			else:
			
				$js[] = self::exportDropboxAsScript();

			endif;

		endforeach;

		// has script tags
		if (count(self::$jsScripts) > 0) foreach (self::$jsScripts as $script) $js[] = $script;

		// deffer
		$deffer = PATH_TO_JS . '/Rexajs/deffer.min.js';
		$parse = parse_url($deffer);

		if (!isset($parse['scheme'])) $deffer = $url . $deffer;

		$js[] = '<script type="text/javascript" src="'.$deffer.'" data-moorexa-appurl="'.func()->url().'"></script>';

		// return js
		return implode("\n\t", $js);
	} 

	/**
	 * @method Assets changePath
	 * @param array $config
	 * @return void 
	 * 
	 * This method changes the base directory to images, css and javascripts 
	 */
	public function changePath(array $config) : void 
	{
		// run through array
		if (count ($config) > 0) :
		
			foreach ($config as $property => $path) :
			
				// set path
				if (property_exists($this, $property)) $this->{$property} = $path;

			endforeach;

		endif;
	}
	
	/**
	 * @method Assets resetPath
	 * @return void 
	 * 
	 * This method resets our base directories to images, css and javascripts
	 */
	public function resetPath() : void
	{
		$this->css_path = func()->const('css') . '/';
		$this->js_path 	= func()->const('js') . '/';
		$this->image_path = func()->const('image') . '/';
	}

	/**
	 * @method Assets exportVars
	 * @param array $data
	 * @return void 
	 * 
	 * This method exports php variables to javascript
	 */
    public static function exportVars(array $data) : void
    {
        self::$phpVarsData[] = $data;
	}

	/**
	 * @method Assets loadSubsribers
	 * @param string $method
	 * @param string $value 
	 * @param string $defaultValue
	 * @return string
	 */
	public function loadSubsribers(string $method, string $value, string $defaultValue = '') : string 
	{
		// update default value
		$defaultValue = $defaultValue == '' ? $value : $defaultValue;

		// check for subscribers
		if (isset(self::$subscribers[$method])) :

			// try load subscribers
			foreach (self::$subscribers[$method] as $callback) :

				// call function
				$returnValue = call_user_func($callback->bindTo($this, static::class), $value);

				// update default value
				if (is_string($returnValue)) $defaultValue = $returnValue;

			endforeach;

		endif;

		// return default value
		return $defaultValue;
	}

	/**
	 * @method Assets subscribe
	 * @param string $method
	 * @param Closure $callback 
	 * @return void
	 */
	public static function subscribe(string $method, Closure $callback) : void
	{
		self::$subscribers[$method][] = $callback;
	}
	
	/**
	 * @method Assets changePositionIfChanged
	 * @param string $typeOfFile
	 * @param array &$referenceArray
	 * @return void
	 * This method would change a file position before rendering
	 */
	private function changePositionIfChanged(string $typeOfFile, array &$referenceArray) : void
	{
		$changePosition = self::$changePosition[$typeOfFile];

		if (count($changePosition) > 0) :
		
			foreach ($changePosition as $fileToChange => $config) :
			
				// get position and other js
				list($position, $otherFile) = $config;

				// get position of other file
				$otherPosition = null;

				// run through reference array
				foreach ($referenceArray as $index => $filePath) :
				
					// get base name
					$basename = basename($filePath);

					if ($basename == basename($otherFile) || $otherFile == $filePath) $otherPosition = $index;

					if ($fileToChange == $filePath || $basename == basename($fileToChange)) :
					
						// get path
						$fileToChange = $filePath;
						
						// remove from position
						unset($referenceArray[$index]);

						// now move file
						switch (strtolower($position)) :
						
							case 'before':
								array_splice($referenceArray, $otherPosition, 2, [$fileToChange, $referenceArray[$otherPosition]]);
							break;

							case 'after':
								array_splice($referenceArray, $otherPosition, 2, [$referenceArray[$otherPosition], $fileToChange]);
							break;

						endswitch;

					endif;

				endforeach;

			endforeach;

		endif;
	}

	/**
	 * @method Assets getFileIfCache
	 * @param string $file
	 * @param string $cacheFile (reference)
	 * @param array $json (reference)
	 * @return bool
	 */
	private function getFileIfCache(string $file = '', &$cacheFile='', &$json=[]) : bool
	{
	    $fileCached = '';

		if (strlen($file) > 1) :
		
			// @var array $incomingUrl
			$incomingUrl = URL::getIncomingUri();

			// @var bool $fileCached
			$fileCached = false;

			// @var string $controller
			$controller = $incomingUrl[0];

			// @var string $cache
			$cache = func()->const('assets') . '/assets.paths.json';

			// get static url
			$static_url = env('bootstrap', 'static_url');

			// get url
			$url = $static_url != '' ? $static_url . '/' : func()->url();

			$json = json_decode(file_get_contents($cache));
			$json = is_null($json) ? [] : func()->toArray($json);

			// @var string $keyName for json
			$keyName = $controller . '.' . $file;

			if (isset($json[$keyName]) && file_exists($json[$keyName])) :
			
				// update $fileCached
				$fileCached = true;

				// update cache file
				$cacheFile = $url . $json[$keyName];
			
			elseif (isset($json[$file]) && file_exists($json[$file])) :

				// update $fileCached
				$fileCached = true;

				// update cache file
				$cacheFile = $url . $json[$file];

			endif;
		
		endif;

		// return bool
		return $fileCached;
	}

	/**
	 * @method Assets exportDropboxAsScript
	 * @return string
	 */
    private static function exportDropboxAsScript() : string
    {
		// @var array $data
		$data = [];
		
		// callbacks
		$callbacks = [];
		
		// merge data
		foreach (self::$phpVarsData as $vars) :
			
			// add callback
			if (isset($vars['callback'])) $callbacks[] = $vars['callback'] . '.call();';
			
			// add export
			if (isset($vars['export'])) $callbacks[] = $vars['export'];
			
			// merge data 
			$data = array_merge($data, $vars);
			
		endforeach;
		
		// add callbacks
		if (count($callbacks) > 0) self::$jsScripts[] = '<script type="text/deffered">window.addEventListener("load", function(){ '.implode(' ', $callbacks).' });</script>';
		
		// return string 
		return '<script type="text/deffered">let phpvars = '.json_encode($data).';</script>';
    }
}