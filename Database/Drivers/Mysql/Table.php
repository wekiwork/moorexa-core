<?php
namespace Lightroom\Database\Drivers\Mysql;

use PDO;
use Lightroom\{
    Core\FunctionWrapper,
    Common\File,
    Adapter\ClassManager,
    Database\Interfaces\DriverQueryInterface,
    Database\Interfaces\SchemaInterface,
    Database\Interfaces\TableInterface,
    Database\Drivers\TableHelper,
    Database\Interfaces\TableHelperInterface,
    Exceptions\ClassNotFound
};
/**
 * @package Mysql Table class
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is the default table class for Mysql database system. With this class, you can do the list of operations below
 * - create a table
 * - check if a table exists
 * - drop a table
 * - get a table information
 * - update a table structure
 */
class Table implements TableInterface, TableHelperInterface
{
    use TableHelper;

    /**
     * @method TableInterface getQueryInstance
     * @return DriverQueryInterface
     * @throws ClassNotFound
     */
    public static function getQueryInstance() : DriverQueryInterface
    {
        return ClassManager::singleton(Query::class);
    }

    /**
     * @method TableInterface getSchemaInstance
     * @return SchemaInterface
     */
    public static function getSchemaInstance() : SchemaInterface
    {
        return new Schema();
    }

    /**
     * @method TableHelperInterface existsStatement
     * @return string
     */
    public static function existsStatement() : string
    {
        return "show tables";
    }

    /**
     * @method TableHelperInterface infoStatement
     * @param string $table
     * @return string
     */
    public static function infoStatement(string $table) : string 
    {
        return "show fields from $table";
    }

    /**
     * @method TableInterface getPrimaryField
     * @param string $table
     * @return string 
     */
    public function getPrimaryField(string $table) : string 
    {
        // @var string $field
        $field = '';

        // @var array info
        $info = self::info($table);

        if (is_array($info)) :

            // get primary key
            foreach ($info as $column)  :
            
                // get field
                if ($column['Key'] == 'PRI') :
                    
                    // update field
                    $field = $column['Field'];
                    
                    // break out
                    break;

                endif;

            endforeach;

        endif;

        // return string
        return $field;
    }
}