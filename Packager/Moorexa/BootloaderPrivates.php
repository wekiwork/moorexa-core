<?php
namespace Lightroom\Packager\Moorexa;

use Symfony\Component\Yaml\Yaml;
use Lightroom\Adapter\Configuration\Environment;

/**
 * @package private methods for bootloader
 */
trait BootloaderPrivates
{
    // read environment variables
    private function readEnvironmentVariables(array $config)
    {
        // check for default.env path
        if (isset($config['default.env'])) :

            // get path
            $path = $config['default.env'];

            // check if path exists
            if (file_exists($path)) :

                // default must be a yaml file
                if (substr($path, -4) != 'yaml') :
                
                    // throw invalid environment file
                    throw new \Lightroom\Exceptions\MoorexaInvalidEnvironmentFile($path);
                
                endif;

                // read environment file
                $environment = class_exists(Yaml::class) ? Yaml::parseFile($path) : [];

                foreach ($environment as $environmentKey => &$environmentVal) :

                    // save to environment vars 
                    $this->saveToEnvironment($environmentKey, $environmentVal);

                endforeach;

                // clean up
                unset($environmentKey, $environmentVal);

            endif;

            // free memory
            unset($path, $environment);

        endif;
    }

    // save configuration to environment 
    public function saveToEnvironment(string $key, $value)
    {
        Environment::setEnv($key, $value);
    }

}