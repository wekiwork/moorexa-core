<?php 
namespace Lightroom\Test;

use Exception, PDO, PDOStatement;
use Lightroom\Adapter\ClassManager;
use Lightroom\Database\DatabaseHandler;
use Lightroom\Packager\Moorexa\TestManager;
use Lightroom\Exceptions\{ClassNotFound, InterfaceNotFound, MethodNotFound};
/**
 * @package DatabaseTest
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait DatabaseTest
{
    /**
     * @method DatabaseTest enable_rollback
     * @return void
     */
    private function enable_rollback() : void
    {
        // disable migratation behaviour for assist manager
        if (!defined('DB_TEST_ENV')) define('DB_TEST_ENV', true);

        // subscribe to every database queries from this point
        DatabaseHandler::subscribe('test-environment', function(PDOStatement $statement, PDO $pdo)
        {
            // rollback the transaction
            $pdo->rollBack();
        });
    }

    /**
     * @method DatabaseTest loadData
     * @param string $placeholder
     * @param array $optionalData
     * @return mixed
     * @throws Exception
     */
    private function loadData(string $placeholder, array $optionalData = [])
    {
        // get the test file and access the collection data from the placeholder
        // given.
        $placeholderArray = explode('@', $placeholder);

        // test file should be on index 1, collection placeholder name should be on index 2
        if (count($placeholderArray) == 2) :

            // get test file and collection placeholder
            list($test_file, $collection) = $placeholderArray;

            // get test directory
            $directory = TestManager::getBaseDirectory();

            // get full path
            $fullPath = $directory . '/data/' . $test_file . '.php';

            // check if test file exists
            if (!\file_exists($fullPath)) throw new Exception('Data File ${'.$fullPath.'} not found');

            // load file now
            $data = include_once $fullPath;

            // read collection
            if (isset($data['collection']) && isset($data['collection'][$collection])) :

                // get collection
                return new class($data, $collection, $optionalData)
                {
                    private $collection = [];
                    private $data = [];
                    private $optionalData = [];

                    /**
                     * @method class@anonymous __construct
                     * @param array $data
                     * @param string $collection
                     * @param array $optionalData
                     */
                    public function __construct(array $data, string $collection, array $optionalData)
                    {
                        // load the collection
                        $collectionKey = $data['collection'][$collection];

                        // try to fetch collection data
                        if (!isset($data[$collectionKey])) throw new Exception('Unknown collection array for data ${'.$collection.'}');

                        // read now
                        $this->collection =& $data[$collectionKey];
                        $this->optionalData =& $optionalData;
                        $this->data =& $data;

                        // can we update ?
                        if (\count($optionalData) > 0) :

                            // load event
                            $this->eventManager('updated');

                            // merge data
                            $merged = array_merge($this->collection, $optionalData);

                            // merge data
                            $this->collection =& $merged;

                        endif;

                        // call accessed event 
                        $this->eventManager('accessed');
                    }

                    /**
                     * @method class@anonymous eventManager
                     * @param string $type
                     * @return void
                     */
                    private function eventManager(string $type) : void
                    {
                        if (isset($this->data['events']) && isset($this->data['events'][$type])) :

                            // load events
                            $event = $this->data['events'][$type];
    
                            // load arguments
                            $arguments = [&$this->collection, &$this->optionalData];
    
                            // continue if it's a closure function
                            if ($event !== null && \is_callable($event)) call_user_func_array($event, $arguments);
    
                        endif;
                    }

                    /**
                     * @method class@anonymous create
                     * @param array $data
                     * @return array
                     */
                    public function create(array $data = []) : array 
                    {
                        return array_merge($this->collection, $data);
                    }

                    /**
                     * @method class@anonymous make
                     * @param array $data
                     * @return mixed
                     */
                    public function make(array $data = []) 
                    {
                        // @var array $data
                        $data = count($data) == 0 ? $this->collection : $data;

                        // return object
                        return new class($data)
                        {
                            /**
                             * @var array $data
                             */
                            private $data = [];

                            // set data
                            public function __construct(array $data)
                            {
                                $this->data = $data;
                            }

                            // get data
                            public function getData() : array 
                            {
                                return $this->data;
                            }
                        };
                    }
                };

            else :
                
                // throw an error
                throw new Exception('Model ${'.$model.'} not found Data File ${'.$fullPath.'}.');

            endif;

        endif;

        // failed
        return false;
    }

    /**
     * @method DatabaseTest database
     * @param array|string $condition
     * @param mixed $data 
     * @return mixed
     */
    private function database($condition, $data = null) 
    {
        // update flag
        $this->flag = 1;

        // load method
        return $this->__testCaseLoader($condition, $data, 'Database');
    }
}