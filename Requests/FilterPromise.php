<?php
namespace Lightroom\Requests;
/**
 * @package FilterPromise
 * @author Amadi Ifeanyi <amadiify.com>
 */
class FilterPromise extends Filter
{
    /**
     * @var array $errors
     */
    protected $errors = [];

    /**
     * @var array $cleanData
     */
    protected $cleanData = [];

    /**
     * @var array $oldConfig
     */
    protected $oldConfig = [];

    /**
     * @var mixed $requestHandler
     */
    private $requestHandler = null;

    /**
     * @method FilterPromise filterReady
     * @param array $errors
     * @param array $cleanData
     * @param mixed $channel
     * @param array $filter
     * @return FilterPromise
     * 
     * This method loads errors and clean data when filter is ready
     */
    public function filterReady(array $errors, array $cleanData, $channel, array $filter) : FilterPromise
    {
        $this->errors = $errors;
        $this->cleanData = $cleanData;
        
        // save old config
        $this->oldConfig = [
            'channel' => $channel,
            'filter' => $filter
        ];

        // return FilterPromise
        return $this;
    }

    /**
     * @method FilterPromise setRequestHandler
     * @param mixed $requestHandler
     * @return void
     */
    public function setRequestHandler($requestHandler) : void 
    {
        $this->requestHandler = $requestHandler;
    }
    
    /**
     * @method FilterPromise getRequestHandler
     * @return mixed
     */
    public function getRequestHandler() 
    {
        return $this->requestHandler;
    }
}