<?php
namespace Lightroom\Templates\Interfaces;

use Closure;
/**
 * @package Template Handler Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface TemplateHandlerInterface
{
    /**
     * @method TemplateHandlerInterface registerEngine
     * @param string $engine
     * @param string $alaise
     * @param Closure $closure
     * @return TemplateHandlerInterface
     */
    public function registerEngine(string $engine, string $alaise, Closure $closure) : TemplateHandlerInterface;

    /**
     * @method TemplateHandlerInterface render
     * @param string $path
     * @param mixed $arguments
     * @return void
     */
    public static function render(string $path, ...$arguments) : void;
}