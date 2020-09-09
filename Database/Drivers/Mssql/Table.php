<?php
namespace Lightroom\Database\Drivers\Mssql;

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
 * @package Mssql Table class
 * @author Amadi Ifeanyi <amadiify.com>
 * 
 * This is the default table class for Mssql database system. With this class, you can do the list of operations below
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
        return "select TABLE_NAME from INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE';";
    }

    /**
     * @method TableHelperInterface infoStatement
     * @param string $table
     * @return string
     */
    public static function infoStatement(string $table) : string 
    {
        return "sp_help $table;";
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

        // get query class
        $database = self::getQueryInstance();

        // @var array column
        $column = $database->sql("SELECT Col.Column_Name from INFORMATION_SCHEMA.TABLE_CONSTRAINTS Tab, INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE Col WHERE 
        Col.Constraint_Name = Tab.Constraint_Name AND Col.Table_Name = Tab.Table_Name AND Constraint_Type = 'PRIMARY KEY' AND Col.Table_Name = '{$table}'");

        if (self::getRows($column) > 0) :

            // get primary key
            $field = $column->fetch(PDO::FETCH_ASSOC)['Column_Name'];

        endif;

        // return string
        return $field;
    }
}