<?php
namespace Lightroom\Database\Drivers\Mysql;

use Lightroom\Database\{
    Interfaces\QueryBuilderInterface, QueryBuilder, Helper
};
use Lightroom\Adapter\ClassManager;
/**
 * @package Query Builder for Mysql
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
class Builder implements QueryBuilderInterface
{
    // use default query builder
    use QueryBuilder;

    // load helper
    use Helper;

    /**
     * @method Query __construct
     * @param array $configuration
     * 
     * This configuration has the pdoInstance, driver, Mysql driver instance and driver class
     */
    public function __construct(array $configuration = []) 
    {
        // load configuration
        if (count($configuration) == 0) :

            // get driver instance
            $driver = ClassManager::singleton(Driver::class);

            // load configuration
            $configuration['driver']      = 'mysql';
            $configuration['handler']     = $driver;
            $configuration['settings']    = $driver->getSettings();
            $configuration['pdoInstance'] = $driver->getActiveConnection();
            $configuration['driverClass'] = Driver::class;

        endif;

        // set the pdo instance
        $this->pdoInstance = $configuration['pdoInstance'];

        // set default prefix
        $this->prefix = $configuration['settings']['prefix'];

        // set driver source
        $this->driverSource = $configuration['settings']['driver.source'];

        // set driver class
        $this->driverClass = Driver::class;

        // load allowed query
        $this->getAllowed();

        // clean up
        $configuration = null;
    }

    /**
     * @method QueryBuilderInterface statements
     * @return array
     * 
     * This loads an array of sql statements for select, update, delete and insert
     * You only need this, if you are using the default query builder.
     */
    public function statements() : array
    {
        return [
            'update' => 'UPDATE {table} SET {query} {where}',
            'insert' => 'INSERT INTO {table} ({column}) VALUES {query}',
            'delete' => 'DELETE FROM {table} {where}',
            'select' => 'SELECT {column} FROM {table} {where}',
        ];
    }
}