<?php
namespace Lightroom\Database\Interfaces;

use PDO;
/**
 * @package Database Handler Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface DatabaseHandlerInterface
{
    /**
     * @method DatabaseHandlerInterface loadConfiguration
     * @param ConfigurationInterface $config
     * @param string $source
     * @return ConfigurationInterface
     */
    public function loadConfiguration(ConfigurationInterface $config, string $source = '') : ConfigurationInterface;
}