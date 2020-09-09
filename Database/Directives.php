<?php
namespace Lightroom\Database;

use PDOStatement;
use Happy\Directives as HappyDirectives;
use Happy\DirectivesInterface;
use function Lightroom\Security\Functions\{encrypt};
use function Lightroom\Database\Functions\{db, db_with, map};

/**
 * @package Database Directives
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Directives implements DirectivesInterface
{
    /**
     * @method DirectivesInterface $instance
     * @return void
     * 
     * After implementing method, you should set the directive name and method with $instance->set(<directive>, <classmethod>)
     */
    public static function directives(HappyDirectives $instance) : void
    {
        $instance->set('fetchRows', 'fetchDatabaseRows');
        $instance->set('fetch', 'fetchFromDatabase');
        $instance->set('endfetch', 'endFetch');
    }

    /**
     * @method Directives fetchFromDatabase
     * @param array $arguments
     * @return string
     */
    public static function fetchFromDatabase(...$arguments) : string
    {
        // get the second argument
        $secondArgument = end($arguments);

        // get ending comma (,)
        $postion = strrpos($secondArgument, ',');

        // extract second argument
        $secondArgument = trim(substr($secondArgument, $postion+1));

        // remove the dollar sign
        $secondArgument = '$' . preg_replace('/[^a-zA-Z0-9\-\_]/', '', $secondArgument);

        // return while statement
        return "<?php \n
        \$query = \Lightroom\Database\Directives::runQuery(".$arguments[1].");\n
        if (\$query->rows > 0){\n
        while ($secondArgument = \$query->obj()){
        ?>\n
        ";
    }

    /**
     * @method Directives fetchDatabaseRows
     * @param string $table
     * @param array $binds
     * @return mixed
     */
    public static function fetchDatabaseRows(string $table, array $binds)
    {
        // add table
        $binds = array_merge(['table' => $table], $binds);

        // return runquery
        return call_user_func_array([static::class, 'runQuery'], [$binds]);
    }

    /**
     * @method Directives endFetch
     * @return string
     */
    public static function endFetch() : string
    {
        return "<?php }} ?>";
    }

    /**
     * @method Directives runQuery
     * @param array $arguments
     * @return mixed
     */
    public static function runQuery(...$arguments)
    {
        // get the firstArgument
        $firstArgument = $arguments[0];

        // @var string $table
        $table = '';

        // @var $statement
        $statement = null;

        // @var db
        $db = null;

        // check if $firstArgument is an array
        if (is_array($firstArgument)) :

            // load table
            if (isset($firstArgument['table'])) $table = $firstArgument['table'];

            // load query
            if ($table != '') :

                // load with
                if (isset($firstArgument['db_with'])) :

                    // move first argument 
                    $db = db_with($firstArgument['db_with']);

                    // remove first argument
                    unset($firstArgument['db_with']);

                else:

                    // load default
                    $db = db();

                endif;

                // load query
                foreach ($firstArgument as $method => $argument) :

                    // load chain of command
                    if (is_string($argument)) $db = call_user_func([$db, $method], $argument);

                    // check method
                    if ($method == 'table') $db->get();

                    // load argument
                    if (is_array($argument)) if (!isset($argument[0])) $argument = [$argument];

                    // load array
                    if (is_array($argument)) call_user_func_array([$db, $method], $argument);

                endforeach;

                // execute query
                $db = map($db);

            endif;

        elseif (is_string($firstArgument)) :

            // load all
            $db = map(db($firstArgument)->get());

            // set table
            $table = $firstArgument;

        elseif (is_object($firstArgument)) :

            // load db
            $db = $firstArgument;

            // get class
            if (get_class($firstArgument) == PDOStatement::class) $db = map($firstArgument);

        endif;

        // return db
        return $db;
    }
}