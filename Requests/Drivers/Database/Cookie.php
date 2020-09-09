<?php
namespace Lightroom\Requests\Drivers\Database;

use PDO;
use Lightroom\Requests\Drivers\DriversHelper;
use function Lightroom\Database\Functions\{db, db_with};
use Lightroom\Database\Interfaces\SchemaInterface;
use function Lightroom\Security\Functions\{encrypt};
use Lightroom\Requests\Interfaces\DatabaseDriverInterface;
/**
 * @package Database driver to cookie
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Cookie implements DatabaseDriverInterface
{
    use DriversHelper;

    /**
     * @var string $tableName
     */
    private $tableName = 'cookie_storage';

    /**
     * @var bool $queryRan
     */
    private static $queryRan = false;

    /**
     * @var array $cookieCached
     */
    private static $cookieCached = [];

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
        $schema->increment('cookieid');
        $schema->string('cookie_userAgent');
        $schema->text('cookie_identifier');
        $schema->longtext('cookie_value');
        $schema->string('cookie_expire');
        $schema->string('cookie_path');
        $schema->string('cookie_domain');
        $schema->tinyint('cookie_secure')->default(0);
        $schema->tinyint('cookie_httponly')->default(0);

        // set schema table
        $schema->table = $this->tableName;
    }

    /**
     * @method Cookie getConnection
     * @return mixed
     */
    public static function getConnection()
    {
        // @var string $connection_name
        $connection_name = self::$connection_name;

        // get connection from identifier
        if ($connection_name == '') :
            
            // get the identifier
            $identifier = env('cookie')['identifer'];

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
        // @var array $cookies
        $cookies = [];

        if (self::$queryRan === true || (count(self::$cookieCached) == 0 && self::$trackCount == 0)) :

            // get user agent
            $agent = $this->getUserAgent();

            // get query
            $query = self::getConnection()->getQuery()->table($this->tableName);

            // find all records from table
            $records = $query->select('cookie_userAgent = ?', $agent);

            // continue if we have records
            if (is_object($records)) :

                // @var array $records
                $records = $records->fetchAll();

                foreach ($records as $record) :

                    // convert to an object
                    $record = (object) $record;

                    // @var bool $pushCookie
                    $pushCookie = true;

                    // check expire time
                    if ($record->cookie_expire < time()) :
                        // remove cookie
                        $query->delete('cookieid = ?', $record->cookieid);

                        // don't push cookie
                        $pushCookie = false;
                    endif;

                    // check cookie path
                    if ($record->cookie_path != '/') if ($_SERVER['REQUEST_URI'] != $record->cookie_path) $pushCookie = false;

                    // check domain
                    if ($record->cookie_domain != '') if ($record->cookie_domain != func()->url())  $pushCookie = false;

                    // check secure cookie
                    if ($record->cookie_secure != 0) :
                    
                        // get protocol
                        $protocol = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : null;
                        
                        // check request scheme
                        if ($protocol == null) $protocol = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : null;

                        // unsecure connection used.
                        if ($protocol == null || $protocol == 'http') $pushCookie = false;
                    
                    endif;

                    // push to array
                    if ($pushCookie) $cookies[$record->cookie_identifier] = $record->cookie_value;

                endforeach;

            endif;

            // update track count
            self::$trackCount = 1;

            // update cache
            self::$cookieCached = $cookies;

            // update query ran
            self::$queryRan = false;
        
        else:
            // load from cache
            $cookies = self::$cookieCached;
        endif;

        // return array
        return $cookies;
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
            'cookie_identifier' => $identifier,
            'cookie_userAgent' => $this->getUserAgent()
        ];

        // @var $query
        $query = self::getConnection()->getQuery()->table($this->tableName);

        // ensure record hasn't been created previously
        if ($query->select($record)->rowCount() == 0) :

            // add value
            $record['cookie_value'] = encrypt($value, $identifier);

            // merge record
            $record = array_merge($record, $options);

            // create record
            $query->insert($record);

        else:

            // add value
            $update = array_merge($record, ['cookie_value' => encrypt($value, $identifier)]);

            // merge options
            $update = array_merge($update, $options);

            // update record
            $query->update($update, $record);

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
        if ($query->delete('cookie_identifier = ? and cookie_userAgent = ?', $identifier, $this->getUserAgent())->rowCount() > 0) $dropped = true;

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
        if ($query->delete('cookie_userAgent = ?', $this->getUserAgent())->rowCount() > 0) $emptied = true;

        // return bool
        return $emptied;
    }
}