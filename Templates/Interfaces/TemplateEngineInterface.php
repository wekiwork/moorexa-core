<?php
namespace Lightroom\Templates\Interfaces;

/**
 * @package Template Engine Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface TemplateEngineInterface
{
    /**
     * @method TemplateEngineInterface init
     * @return void
     * 
     * This method would be called after registering template engine
     */
    public function init() : void;

    /**
     * @method TemplateEngineInterface aliaseUsed
     * @param string $alaise
     * @return void
     * 
     * This method would register the alaise used for this template engine
     */
    public function aliaseUsed(string $alaise) : void;

    /**
     * @method TemplateEngineInterface externalCall
     * @param string $method
     * @param array $arguments
     * @return mixed
     *
     * This method would be called when there is an external method request. Possibly from the template handler
     */
    public function externalCall(string $method, ...$arguments);

    /**
     * @method TemplateEngineInterface parseTextContent
     * @param string $content
     * @return string
     * 
     * This method would parse text content when extends function is called
     */
    public function parseTextContent(string $content) : string;
}