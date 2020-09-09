<?php
namespace Lightroom\Database;

use Lightroom\Adapter\Configuration\Environment;

/**
 * @package Database connection settings
 * @author Amadi Ifeanyi <amadiify.com>
 */
class ConnectionSettings
{
    /**
     * @var ConnectionSettings $instance
     */
    private static $instance;

    /**
     * @var array $configuration
     */
    private static $configuration = [];

    /** 
     * @var string $defaultConfiguration
     */ 
    private static $defaultSource;

    /**
     * @var bool $domain_served
     */
    private static $domain_served = false;

    /**
     * @method ConnectionSettings load
     * @param array $connection
     * Load an array of database connection settings
     * @return ConnectionSettings
     */
    public static function load(array $connection) : ConnectionSettings
    {
        // create instance
        if (is_null(self::$instance)) self::$instance = new self;

        // save configuration
        self::$configuration = array_merge(self::$configuration, $connection);

        // return instance
        return self::$instance;
    }

    /**
     * @method ConnectionSettings default
     * @param array $default
     * Loads the default database identifier for development or production
     * @return ConnectionSettings
     */
    public function default(array $default) : ConnectionSettings
    {
        // use production configuration
        $continue = Environment::getEnv('database', 'mode') != 'development' ? true : false;

        // set default source
        self::$defaultSource = $continue ? $default['live'] : $default['development'];

        // return instance
        return $this;
    }

    /**
     * @method ConnectionSettings domain
     * @param string $domain
     * @param string $identifier
     * @return ConnectionSettings
     */
    public function domain(string $domain, string $identifier, $callback = null) : ConnectionSettings
    {
        if (self::$domain_served === false) :

            // check for remote_address
            $remote_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

            // check for server_name
            $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;

            // check for http_host
            $http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;

            // check for script_name
            $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : null;

            // check now
            if (($remote_address == $domain) || ($server_name == $domain) || ($http_host == $domain)) :

                // set default source
                self::$defaultSource = $identifier;

                // found
                self::$domain_served = true;

                // call closure
                if ($callback !== null && is_callable($callback)) : call_user_func($callback); endif;

            else:

                if ($script_name !== null) :

                    // remove base
                    $base = basename($script_name);
                    $script_name = ltrim($script_name, $base);

                    // check server name
                    if ($server_name != null && ($server_name . $script_name == $domain)) :

                        // set default source
                        self::$defaultSource = $identifier;

                        // found
                        self::$domain_served = true;

                        // call closure
                        if ($callback !== null && is_callable($callback)) : call_user_func($callback); endif;

                    endif;

                endif;

            endif;

        endif;

        // return ConnectionSettings
        return $this;
    }

    /**
     * @method ConnectionSettings readConfiguration
     * @param string $source
     * @param array $settings (reference)
     *
     * This method reads a configuration setting with a source
     * @return array
     */
    public static function readConfiguration(string $source, array &$settings = []) : array
    {
        // configuration
        $configuration = self::$configuration;

        // use production configuration
        $continue = Environment::getEnv('database', 'mode') != 'development' ? true : false;

        // @var string $action
        $action = '';
        
        // read source for action
        if (strrpos($source, '@') !== false) :
        
            $action = substr($source, strpos($source, '@')+1);
            $source = substr($source, 0, strpos($source, '@'));
        
        endif;

        // check configuration size
        if (count($configuration) > 0) :

            // load settings
            $settings = isset($configuration[$source]) ? $configuration[$source] : $settings;

            // continue loading production config?
            if ($continue) :

                if (isset($configuration[$source])) :
                    // update configuration with production configuration
                    self::settingsVars($settings, $configuration[$source], $action);
                else:
                    $settings = [];
                endif;
            else :

                // check if action is not empty
                if ($action != '') :
                    // update configuration with $action configuration
                    self::settingsVars($settings, $configuration[$source], $action);
                endif;
                
            endif;

        endif;

        // return array
        return $settings;
    }

    /**
     * @method ConnectionSettings getDefault
     * @return array
     * Gets the default connection settings
     */
    public static function getDefault() : array
    {
        // @var array $settings
        $settings = [];

        // get default connection source name
        $default = self::$defaultSource;

        // check if a default connection source has been set
        if ($default !== '') :

            // try get connection configuration
            $configuration = self::readConfiguration($default);

            // update settings array
            $settings = $configuration;

        endif;

        // return array
        return $settings;
    }

    /**
     * @method ConnectionSettings getDefaultSource
     * @return string
     */
    public static function getDefaultSource() : string
    {
        return self::$defaultSource;
    }

    /**
     * @method ConnectionSettings settingsVars
     * @param array $settings (reference)
     * @param array $configuration
     * @param string $action
     * @return void
     * 
     * This reads and replaces the configuration settings with a default action
     * How it works?
     * 
     * In your database.php, you can switch a connection settings easily with the @ sign
     * Take this example;
     * 
     * ->default(['development' => 'demo@demo-server', 'live' => '']);
     * 
     * now, in your demo data source, you can add this
     * 
     * 'demo' => [
     *   'demo-server' => [
     *       'host' => 'new host',
     *       'dbname' => 'new name'
     *       etc..
     *    ]
     */
    private static function settingsVars(array &$settings = [], array $configuration, string $action = 'production') : void
    {
        // check if action exists in configuration
        if (isset($configuration[$action])) :
        
            // get configuration from action
            $sourceData = $configuration[$action];

            // get type
            switch (gettype($sourceData)) :
            
                // is array?
                case 'array':

                    // trying to get the configuration
                    foreach ($sourceData as $key => $value) :

                        if (isset($settings[$key])) :
                            // remove key
                            unset($settings[$key]);
                        endif;

                    endforeach;

                    // remove action from connection vars
                    unset($settings[$action]);

                    // merge with source data
                    $settings = array_merge($settings, $sourceData);

                break;

                // is string?
                case 'string':

                    // read configuration
                    $configuration = self::readConfiguration($sourceData[$action]);

                    // check if configuration exists and $action is not a child of $configuration settings
                    if ($configuration !== null && !isset($configuration[$action])) :
                    
                        // replace settings with $configuration
                        $settings = $configuration;
                    
                    else:
                    
                        // read $settings vars
                        self::settingsVars($settings, $configuration);

                    endif;

                break;

            endswitch;

            // clean up
            $sourceData = null;
        
        endif;
    }
}