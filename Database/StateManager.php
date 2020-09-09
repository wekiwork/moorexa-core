<?php

use Lightroom\Adapter\ClassManager;
use Lightroom\Database\StateManagerClass;
/**
 * @package DatabaseHandler StateManager
 * @author Amadi Ifeanyi <amadiify.com>
 */
if (!function_exists('state')) :

    function state(string $stateName = '')
    {
        // @var mixed $stateManager
        static $stateManager;

        // create state manager class
        if (is_null($stateManager)) $stateManager = ClassManager::singleton(StateManagerClass::class);

        // load new instance
        if ($stateName !== '') return new StateManagerClass($stateName);

        // return instance
        return $stateManager;
    }

endif;