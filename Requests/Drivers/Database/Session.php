<?php
namespace Lightroom\Requests\Drivers\Database;

use PDO;
use Lightroom\Requests\Drivers\DriversHelper;
use function Lightroom\Database\Functions\{db, db_with, rows};
use Lightroom\Database\Interfaces\SchemaInterface;
use function Lightroom\Security\Functions\{encrypt};
use Lightroom\Requests\Interfaces\DatabaseDriverInterface;
/**
 * @package Database driver to session
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Session implements DatabaseDriverInterface
{
    use DriversHelper;

    /**
     * @var string $tableName
     */
    private $tableName = 'session_storage';

    /**
     * @var bool $queryRan
     */
    private static $queryRan = false;

    /**
     * @var array $sessionCached
     */
    private static $sessionCached = [];

    /**
     * @var int $trackCount
     */
    private static $trackCount = 0;

    /**
     * @var string $connection_name
     */
    public static $connection_name = '';

    /**
     * @method DatabaseDriverInterface up
     * @return void
     */
    public function up(SchemaInterface $schema) : void
    {
        // build table schema structure
        $schema->increment('sessionid');
        $schema->text('session_identifier');
        $schema->longtext('session_value');
        $schema->text('user_agent')->null();
        $schema->datetime('date_created')->current();

        // set schema table
        $schema->table = $this->tableName;
    }

    /**
     * @method Session getConnection
     * @return mixed
     */
    public static function getConnection()
    {
        // @var string $connection_name
        $connection_name = self::$connection_name;

        // get connection from identifier
        if ($connection_name == '') :
            
            // get the identifier
            $identifier = env('session')['identifier'];

            // check length
            if (strlen($identifier) > 1) $connection_name = $identifier;
            
        endif;

        // return connection by identifier
        if ($connection_name != '') return db_with($connection_name);

        // return default
        return db();
    }

    /**
     * @method DatabaseDriverInterface getAll
     * @return array
     */
    public function getAll() : array
    {
        // @var array $session
        $session = [];

        if (self::$queryRan === true || (count(self::$sessionCached) == 0 && self::$trackCount == 0)) :

            // get user agent
            $agent = $this->getUserAgent();

            // find all records from table
            $records = self::getConnection()->getQuery()->table($this->tableName)->select();

            // continue if we have records
            if (is_object($records)) :

                $records = $records->fetchAll();

                if (count($records) > 0) :

                    foreach ($records as $record) :
                        // push to array
                        $session[$record['session_identifier']] = $record['session_value'];
                    endforeach;

                endif;

            endif;

            // update track count
            self::$trackCount = 1;

            // update cache
            self::$sessionCached = $session;

            // update query ran
            self::$queryRan = false;
        
        else:

            // load from cache
            $session = self::$sessionCached;

        endif;


        // return array
        return $session;
    }

    /**
     * @method DatabaseDriverInterface createRecord
     * @param string $identifier
     * @param mixed $value
     * @param array $options
     * @return void
     */
    public function createRecord(string $identifier, $value, array $options = []) : void
    {
        // build record
        $record = [
            'session_identifier' => $identifier,
            'user_agent' => $this->getUserAgent()
        ];

        // @var $query
        $query = self::getConnection()->getQuery()->table($this->tableName);

        // ensure record hasn't been created previously
        if (rows($query->select($record)) == 0) :

            // add value
            $record['session_value'] = encrypt($value, $identifier);

            // create record
            $query->insert($record);

        else:

            // update record
            $query->update(['session_value' => encrypt($value, $identifier)], $record);

        endif;

        // query ran 
        self::$queryRan = true;
    }

    /**
     * @method DatabaseDriverInterface dropRecord
     * @param string $identifier
     * @return bool
     */
    public function dropRecord(string $identifier) : bool
    {
        // @var bool $dropped 
        $dropped = false;

        // get query
        $query = self::getConnection()->getQuery()->table($this->tableName);

        // run query
        if (rows($query->delete('session_identifier = ? and user_agent = ?', $identifier, $this->getUserAgent())) > 0) $dropped = true;

        // return bool
        return $dropped;
    }

    /**
     * @method DatabaseDriverInterface emptyRecords
     * @return bool
     */
    public function emptyRecords() : bool
    {
        // @var bool $emptied
        $emptied = false;

        // get query
        $query = self::getConnection()->getQuery()->table($this->tableName);

        // run query
        if (rows($query->delete('user_agent = ?', $this->getUserAgent())) > 0) $emptied = true;

        // return bool
        return $emptied;
    }
}