<?php
namespace Lightroom\Database\Interfaces;

/**
 * @package SchemaHelper Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface SchemaHelperInterface
{
    /**
     * @method SchemaHelperInterface __rename
     * @param string $table
     * @param string $newName
     * @return string
     */
    public function __rename(string $table, string &$newName) : string;

    /**
     * @method SchemaHelperInterface __engine
     * @param string $table
     * @param string $engine
     * @return string
     */
    public function __engine(string $table, string $engine) : string;

    /**
     * @method SchemaHelperInterface __collation
     * @param string $table
     * @param string $charset
     * @param string $collation
     * @return string
     */
    public function __collation(string $table, string $charset, string $collation) : string;

    /**
     * @method SchemaHelperInterface __createStatement
     * @return string
     */
    public function __createStatement() : string;

    /**
     * @method SchemaHelperInterface __current
     * @return string
     */
    public function __current() : string;

    /**
     * @method SchemaHelperInterface __increment
     * @param string $method
     * @param mixed $length
     * @param mixed $other
     * @return void
     */
    public function __increment(string &$method, &$length, &$other) : void;

    /**
     * @method SchemaHelperInterface __unique
     * @param string $column
     * @return void
     */
    public function __unique(string $column) : void;
}