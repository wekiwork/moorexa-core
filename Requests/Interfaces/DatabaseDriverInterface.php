<?php
namespace Lightroom\Requests\Interfaces;

use Lightroom\Database\Interfaces\SchemaInterface;
/**
 * @package DatabaseDriverInterface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface DatabaseDriverInterface
{
    /**
     * @method DatabaseDriverInterface getAll
     * @return array
     */
    public function getAll() : array;

    /**
     * @method DatabaseDriverInterface up
     * @return void
     */
    public function up(SchemaInterface $schema) : void;

    /**
     * @method DatabaseDriverInterface createRecord
     * @param string $identifier
     * @param mixed $value
     * @param array $options
     * @return void
     */
    public function createRecord(string $identifier, $value, array $options = []) : void;

    /**
     * @method DatabaseDriverInterface dropRecord
     * @param string $identifier
     * @return bool
     */
    public function dropRecord(string $identifier) : bool;

    /**
     * @method DatabaseDriverInterface emptyRecords
     * @return bool
     */
    public function emptyRecords() : bool;
}