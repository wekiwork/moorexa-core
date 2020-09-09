<?php
namespace Lightroom\Core\Interfaces;

/**
 * @package Payload Process runner interface
 * @author fregatlab <fregatelab.com>
 * @author Amadi ifeanyi <amadiify.com>
 */

 interface PayloadRunnerInterface
 {
     /**
      * @method setPayloads
      * You should create a property for payloads
      * @param array $payloads
      */
    public function setPayloads(array $payloads);

     /**
      * @method callLoadProcessWhenComplete
      * @param int $processIndex
      * @param array $processCalled
      */
    public function callLoadProcessWhenComplete(int $processIndex, array &$processCalled);

     /**
      * @method loadProcess
      * @param string $process
      * @param array $processCalled
      * @return bool
      */
    public function loadProcess(string $process, array &$processCalled) : bool;
 }