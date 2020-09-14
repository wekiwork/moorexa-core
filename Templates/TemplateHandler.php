<?php
namespace Lightroom\Templates;

use Closure;
use Lightroom\Exceptions\{
    ClassNotFound, InterfaceNotFound
};
use Exception;
use ReflectionException;
use Lightroom\Templates\Interfaces\{
    TemplateHandlerInterface, TemplateEngineInterface
};
/**
 * @package Template Handler
 * @author Amadi Ifeanyi <amadiify.com>
 */
class TemplateHandler implements TemplateHandlerInterface
{
    /**
     * @var array templateHandlers
     */
    private static $templateHandlers = [];

    /**
     * @var string $aliase
     */
    private $aliase = '';

    /**
     * @var array $defaultHandler
     */
    private static $defaultHandler = null;

    /**
     * @var bool $renderCalled
     */
    private static $renderCalled = false;

    /**
     * @method TemplateHandler
     * @param Closure $closure
     */
    public function __construct(\Closure $closure)
    {
        // load callback function
        call_user_func($closure->bindTo($this, static::class), $this);

        //load template functions
        include_once __DIR__ . '/Functions.php';
    }

    /**
     * @method TemplateHandlerInterface registerEngine
     * @param string $engine
     * @param string $alaise
     * @param Closure $closure
     * @return TemplateHandlerInterface
     * @throws ClassNotFound
     * @throws InterfaceNotFound
     * @throws ReflectionException
     */
    public function registerEngine(string $engine, string $alaise, Closure $closure) : TemplateHandlerInterface
    {
        if (!isset(self::$templateHandlers[$engine])) :

            //  check if class exists
            if (!class_exists($engine)) throw new ClassNotFound($engine);

            // get class reflection instance
            $reflection = new \ReflectionClass($engine);

            // be sure class implements the TemplateEngineInterface
            if (!$reflection->implementsInterface(TemplateEngineInterface::class)) throw new InterfaceNotFound($engine, TemplateEngineInterface::class);

            // update template engine
            $this->templateEngine = $engine;

            // update alaise
            $this->aliase = $alaise;

            // get class instance
            $instance = $reflection->newInstanceWithoutConstructor();

            // call closure and bind $instance to closure
            call_user_func($closure->bindTo($instance, \get_class($instance)));

            // register template engine
            self::$templateHandlers[$alaise] = ['instance' => $instance, 'alaise' => $alaise, 'engine' => $engine];

            // loas aliaseUsed method
            $instance->aliaseUsed($alaise);

            // load init method
            $instance->init();

        endif;

        // return TemplateHandlerInterface
        return $this;
    }

    /**
     * @method TemplateHandler aliase
     * @param string $aliase
     * @return string
     */
    public function aliase(string $aliase) : string 
    {
        return $aliase;
    }

    /**
     * @method TemplateHandler default
     * @return void
     */
    public function default() : void 
    {
        // @var array $defaultHandler
        self::$defaultHandler = self::$templateHandlers[$this->aliase];
    }

    /**
     * @method TemplateHandlerInterface render
     * @param mixed $path
     * @param mixed $arguments
     * @return void
     * @throws Exception
     */
    public static function render($path, ...$arguments) : void
    {
        if (is_string($path) && !preg_match('/[<\{]/', $path)) :

            if (self::$renderCalled === false) :

                // @var TemplateEngineInterface $engine
                $engine = self::getTemplateEngine($path);

                // push $path to the first index
                array_unshift($arguments, 'render', $path);

                // load external call
                call_user_func_array([$engine, 'externalCall'], $arguments);

            endif;

        else:

            // maybe xml or json string
            if (self::$renderCalled === false) self::formatDataAndRender($path);
            
        endif;
    }

    /**
     * @method TemplateHandlerInterface redirect
     * @param string $path
     * @param mixed $arguments
     * @return mixed
     * @throws Exception
     */
    public static function redirect(string $path = '', array $arguments = [])
    {
        if (self::$renderCalled === false) :

            // @var TemplateEngineInterface $engine
            $engine = self::getTemplateEngine($path);

            // load external call
            return call_user_func_array([$engine, 'externalCall'], ['redirect', $path, $arguments]);

        endif;
    }

    /**
     * @method TemplateHandler getTemplateEngine
     * @param string $path
     * @return TemplateEngineInterface
     * @throws Exception
     */
    public static function getTemplateEngine(string $path) : TemplateEngineInterface
    {
        // @var array $pathExploded
        $pathExploded = explode('.', $path);

        // @var TemplateEngineInterface $engine
        $engine = null;

        // continue if size is greater than 1
        if (count($pathExploded) > 1) :

            // get alaise
            $alaise = end($pathExploded);

            // check if alaise exists and update engine
            if (isset(self::$templateHandlers[$alaise])) $engine = self::$templateHandlers[$alaise]['instance'];

        endif;

        // get default if $engine is null
        if ($engine === null && self::$defaultHandler !== null) $engine = self::$defaultHandler['instance'];

        // throw exception if no default handler has been registered
        if ($engine === null) throw new Exception('No default template engine has been set.');

        // return TemplateEngineInterface
        return $engine;
    }

    /**
     * @method TemplateHandler getTemplateHandler
     * @param string $alaise
     * @return TemplateEngineInterface
     */
    public static function getTemplateHandler(string $alaise) : TemplateEngineInterface 
    {
        // @var TemplateEngineInterface $handler
        $handler = null;

        // load handler
        if (isset(self::$templateHandlers[$alaise])) $handler = self::$templateHandlers[$alaise]['instance'];

        // return handler
        return $handler;
    }

    /**
     * @method TemplateHandler formatDataAndRender
     * @param mixed $data
     * @return void
     */
    private static function formatDataAndRender($data) : void 
    {
        // @var string $output
        $output = '';

        // check for string
        if (is_string($data)) :

            // check for xml data
            if (preg_match('/[<][\/](.*?)[>]/', $data)) :

                // change the content type
                header('Content-Type: application/xml');

                // check for xml starting tag
                if (strpos($data, '<?xml') !== false) :

                    // print xml data
                    $output = $data;

                else:

                    // build xml data
                    $output = '<?xml version="1.0"?>
                    <response> ' . $data . '</response>';

                endif;

            else:

                // change the content type
                header('Content-Type: application/json');

                // print json data
                $output = $data;

            endif;

        elseif (is_array($data)) :

            // get content type
            $usingJson = false;

            // xml
            $usingXml = false;

            // check now 
            $headers = headers_list();

            // check for json or xml
            foreach ($headers as $header) :

                // convert to lower case
                $header = strtolower($header);

                // check for json
                if (strpos($header, 'application/json') !== false) $usingJson = true;

                // check for xml
                if (strpos($header, 'application/xml') !== false) $usingXml = true;

            endforeach;

            // load for json
            if ($usingJson) $output = json_encode($data, JSON_PRETTY_PRINT);

            // load for xml
            if ($usingXml) :

                // build tag
                $tags = [];

                // build tag
                foreach ($data as $tag => $value) $tags[] = '<'. $tag . '>' . $value . '</' . $tag . '>';

                // build xml data
                $output = '<?xml version="1.0"?>
                <response> ' . implode("\n", $tags) . '</response>';

            endif;

            // use default
            if ($usingXml === false && $usingJson === false) :

                // change the content type
                header('Content-Type: application/json');

                // print json data
                $output = json_encode($data, JSON_PRETTY_PRINT);

            endif;

        endif;

        // check for output
        if ($output !== '') :

            self::$renderCalled = true;

            // render output
            echo $output;

        endif;
    }
}