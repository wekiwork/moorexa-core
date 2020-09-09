<?php
namespace Lightroom\Database\Interfaces;

/**
 * @package TableHelper Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface TableHelperInterface
{
    /**
     * @method TableHelperInterface existsStatement
     * @return string
     */
    public static function existsStatement() : string;

    /**
     * @method TableHelperInterface infoStatement
     * @param string $table
     * @return string
     */
    public static function infoStatement(string $table) : string;
}