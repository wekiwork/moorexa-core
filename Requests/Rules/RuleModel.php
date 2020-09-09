<?php
namespace Lightroom\Requests\Rules;

use Lightroom\Database\Interfaces\DriverInterface;
use Lightroom\Requests\Rules\Interfaces\InputInterface;
/**
 * @package RuleModel
 * @author Amadi Ifeanyi <amadiify.com>
 */

class RuleModel
{
    // tables
    private static $tables = [];

    // current table
    public $table = null;

    // transactions
    public $transactions = [];

    // promise returned after query using exists method
    private $queryReturned;

    // database instance
    private $databaseInstance;

    // input|rule instance
    private $inputInstance;

    // primary keys
    private static $primaryKeys = [];

    // caller method
    public function __call($meth, $args)
    {
        if ($meth == 'has')
        {
            return $this->_has($args[0]);
        }
        else
        {
            $req = $this->switchOptions($meth, $args);

            if ($req !== null)
            {
                return $req;
            }
        }

        return $this;
    }

    // switch options
    public function switchOptions($meth, $args, $defaultToDB = true)
    {
        if ($meth == 'update' && count($args) == 0)
        {
            return $this->__update();
        }
        elseif ($meth == 'fetch' || $meth == 'get' && count($args) == 0)
        {
            return $this->__fetch();
        }
        elseif ($meth == 'remove' && count($args) == 0)
        {
            return $this->__remove();
        }
        elseif (isset($this->__rules__[$meth]))
        {
            if (!isset($args[0]))
            {
                return $this->getRule($meth);
            }
            else
            {
                $this->__rules__[$meth]['value'] = $args[0];

                // re-validate and remove from required
                $this->revalidate($meth, $args[0]);
            }

            return $this;
        }
        else
        {
            if ($defaultToDB)
            {
                // get query
                $query = $this->getDatabase()->getQuery()->table($this->table);

                // call method
                return call_user_func_array([$query, $meth], $args);
            }
        }

        return null;
    }

    // create record
    public function create(&$id=0) : bool
    {
        // input
        $input = $this->getInputInstance();

        // @var int $created
        $created = false;

        if ($input->isOk()) :
        
            // get query
            $query = $this->getDatabase()->getQuery()->table($this->table);

            // create record
            $insert = $query->insert($input->getData());

            if ($insert->rowCount() > 0) :
            
                // good
                $this->transactions['create'.ucfirst($this->table)]['args'] = [$insert->lastInsertId()];
                $this->queryReturned = $insert;
                $created = true;

            endif;

        endif;

        // boolean
        return $created;
    }

    // update record
    public function __update()
    {
        // input
        $input = $this->getInputInstance();

        if ($this->isOk()) :
        
            // get primary field and id
            list($id, $primary) = $this->getPrimaryKey($input);

            if (!is_null($id)) :
            
                // get query
                $query = $this->getDatabase()->getQuery()->table($this->table);

                // run update
                $update = $query->update($input->getData(), $primary . ' = ?', $id);

                // store last query ran
                $this->queryReturned = $update;

                if ($update->rowCount() > 0) return true;

                return false;
            
            endif;

            $error = 'Primary KEY #{'.$primary.'} not found in rules';

            throw new \Exception($error);

        endif;

        return false;
    }

    // delete record
    public function __remove()
    {
        // input
        $input = $this->getInputInstance();

        if ($this->isOk()) :
        
            // get primary field and id
            list($id, $primary) = $this->getPrimaryKey($input);

            if (!is_null($id)) :
            
                // get query
                $query = $this->getDatabase()->getQuery()->table($this->table);

                // run delete
                $delete = $query->delete($primary . ' = ?', $id);

                // store last query ran
                $this->queryReturned = $delete;

                if ($delete->rowCount() > 0) return true;

                return false;

            endif;

            $error = 'Primary KEY #{'.$primary.'} not found in rules';

            throw new \Exception($error);
        
        endif;

        return false;
    }

    // fetch record
    public function __fetch()
    {
        // input
        $input = $this->getInputInstance();

        if ($input->isOk())
        {
            // get primary field and id
            list($id, $primary) = $this->getPrimaryKey($input);

            // get query
            $query = $this->getDatabase()->getQuery()->table($this->table);

            // @var InputInterface $input
            $input = $this->getInputInstance();

            if (!is_null($id)) :

                // set primary key
                $input->{$primary} = $id;

            endif;

            // run select statement
            if ($primary !== null) return $query->select($input->getData());
            
            $error = 'Primary KEY #{'.$primary.'} not found in rules';

            throw new \Exception($error);
        }

        return false;
    }

    // get primary field and id
    private function getPrimaryKey(InputInterface $input) : array
    {
        // @var mixed $id 
        $id = null;

        // @var mixed $primary;
        $primary = null;

        // identity created ?
        if (count($input->identityCreated) == 0) :
        
            if (!isset(self::$primaryKeys[$this->table])) :

                // get table instance
                $table = get_class($this->getDatabase()->getTable());

                // primary key
                $primary = call_user_func([$table, 'info'], $this->table, function($info){
                    // get primary key
                    return $info('PRI')[0];
                })->Field;

                // store primary key
                self::$primaryKeys[$this->table] = $primary;

            else:

                // get from storage
                $primary = self::$primaryKeys[$this->table];

            endif;
            
            // get id
            $id = $input->getRule($primary);
        
        else:

            // get primary key
            $primary = array_keys($input->identityCreated)[0];

            // get id
            $id = $input->identityCreated[$primary];

            // reset
            $input->identityCreated = [];

        endif;

        // return array
        return [$id, $primary];
    }

    public function asDefault()
    {
        $this->getInputInstance()->__rules__ = $this->__rules__;

        return $this;
    }

    // set table name
    public function setTable(string $tableName, $identity = null)
    {
        $this->table = $tableName;

        if ($identity !== null) $this->identity($identity);

        return $this;
    }

    // match if not all
    public function ifNotAll(...$arguments)
    {
        // build query
        $where = [];

        foreach ($arguments as $value) $where[$value] = $this->getRule($value);

        // get query
        $query = $this->getDatabase()->getQuery();

        // run get request
        $check = $query->table($this->table)->select($where);

        $this->queryReturned = $check;

        if ($check->rowCount() == 0) return true;

        // clean up
        $args = null;

        // failed
        return false;
    }

    // match if not all
    public function ifNotSome(...$arguments)
    {
        // build query
        $where = [];

        foreach ($arguments as $value) $where[$value] = $this->getRule($value);

        // get query
        $query = $this->getDatabase()->getQuery();

        // run get request
        $check = $query->table($this->table)->select($where, 'OR');

        $this->queryReturned = $check;

        if ($check->rowCount() == 0) return false;

        // clean up
        $args = null;

        // success
        return true;
    }

    // exists
    public function exists(&$check=null)
    {
        // input
        $input = $this->getInputInstance();

        if ($input->isOk()) :
        
            // get query
            $query = $this->getDatabase()->getQuery();

            // run check
            $check = $query->table($this->table)->select($input->getData());

            if ($check->rowCount() > 0) :
            
                if (count($input->identityCreated) > 0) $input->pushObject($check);
                
                // set query returned
                $this->queryReturned = $check;

                // passed
                return true;

            endif;
            
        endif;

        // failed.
        return false;
    }

    // factory settings
    public function toFactory($var, $val)
    {
        $this->{$var} = $val;

        return $this;
    }

    // promise method returned
    public function getQuery()
    {
        return $this->queryReturned;
    }

    // promise returned for last query
    public function lastQuery()
    {
        return $this->getQuery();
    }

    // set database instance
    public function setDatabase(DriverInterface $database) : void 
    {
        $this->databaseInstance = $database;
    }

    // get database instance
    public function getDatabase() : DriverInterface 
    {
        return $this->databaseInstance;
    }

    // set input instance
    public function setInputInstance(InputInterface $input) : void 
    {
        $this->inputInstance =& $input;
    }

    // get input instance
    public function getInputInstance() : InputInterface 
    {
        return $this->inputInstance;
    }

}