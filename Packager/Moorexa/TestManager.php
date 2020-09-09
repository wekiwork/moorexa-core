<?php
namespace Lightroom\Packager\Moorexa;

use Exception, Console_Table;
use Classes\Cli\{CliInterface, Assist};
use Symfony\Component\Yaml\Yaml;
use Lightroom\Exceptions\MethodNotFound;
/**
 *@package Basic Test Manager for the framework
 *@author Amadi Ifeanyi <amadiify.com>
 */
class TestManager implements CliInterface
{
    /**
     * @var string $baseDirectory
     */
    private static $baseDirectory = '';

    /**
     * @var string $config
     */
    public static $config = [];

    /**
     * @method CliInterface loadBash
     * @return array
     * @throws Exception
     */
     public static function loadBash() : array
     {
         return [
             'test' => [
                 'start' => function(string $command, array $argv)
                 {
                    // try read test.yaml file in the root directory and 
                    $yaml = APPLICATION_ROOT . '/test.yaml';

                    if (file_exists($yaml)) :

                        // read config file
                        $config = Yaml::parseFile($yaml);

                        // get base directory
                        if (!isset($config['base_directory'])) throw new Exception('Base directory not found in settings.');

                        // create directory if it does not exists
                        if (!\is_dir($config['base_directory'])) \mkdir($config['base_directory']);

                        // push global
                        self::$baseDirectory = $config['base_directory'];

                        // push config global
                        self::$config = $config;

                    endif;

                    // listen for command option
                    self::listenForCommand($argv);
                    
                 }
             ]
         ];
     }

     
    /**
     * @method TestManager listenForCommand
     * @param array $argv
     * @return void
     */
    private static function listenForCommand(array $argv) : void 
    {
        // get the command
        $command = isset($argv[0]) ? $argv[0] : ''; 

        // create instance of assist class
        $assist = new Assist();

        // listen for command 
        switch (\strtolower($command)) :

            // make a test file
            case 'make':

                // this would create a test file
                $testFile = isset($argv[1]) ? $argv[1] : null;

                // fail if testfile is null
                if (\is_null($testFile)) Assist::out($assist->ansii('red') . 'Could not make test file. no name sent.' . $assist->ansii());

                // create test file
                if (!\is_null($testFile)) :

                    // read layout file
                    $testLayout = FRAMEWORK_BASE_PATH . '/Test/TestLayout.txt';

                    // continue if test layout exists
                    if (\file_exists($testLayout)) :

                        // read layout file
                        $content = \file_get_contents($testLayout);

                        // get class name
                        $className = \preg_replace('/[\s]+/', '', ucwords(\preg_replace('/[-]/', ' ', $testFile)));

                        // replace {className} with 
                        $content = str_replace('{className}', $className, $content);

                        // save file
                        $path = self::$baseDirectory . '/' . $className . '.php';

                        // continue if file does not exists
                        if (!\file_exists($path)) :

                            // save file
                            \file_put_contents($path, $content);

                            // all done
                            Assist::out($assist->ansii('green') . $className . ' Test File created successfully.' . $assist->ansii());

                        else:

                            // can not proceed, file exists
                            Assist::out($assist->ansii('red') . 'Could not make test file. Reason: ['.$path.'] exists.' . $assist->ansii());
                        endif;

                    else:

                        // could not find test layout
                        Assist::out($assist->ansii('red') . 'Could not find layout file in ['.$testLayout.']' . $assist->ansii());

                    endif;

                endif;

            break;

            // make test data
            case 'make:data':

                // this would create a test data file
                $testDataFile = isset($argv[1]) ? $argv[1] : null;

                // fail if test data file is null
                if (\is_null($testDataFile)) Assist::out($assist->ansii('red') . 'Could not make test data file. no name sent.' . $assist->ansii());

                // create test file
                if (!\is_null($testDataFile)) :

                    // read layout file
                    $testLayout = FRAMEWORK_BASE_PATH . '/Test/TestDataLayout.txt';

                    // continue if test layout exists
                    if (\file_exists($testLayout)) :

                        // read layout file
                        $content = \file_get_contents($testLayout);

                        // save file
                        $path = self::$baseDirectory . '/data/' . $testDataFile . '.php';

                        // continue if file does not exists
                        if (!\file_exists($path)) :

                            // save file
                            \file_put_contents($path, $content);

                            // all done
                            Assist::out($assist->ansii('green') . $testDataFile . ' Test Data File created successfully.' . $assist->ansii());

                        else:

                            // can not proceed, file exists
                            Assist::out($assist->ansii('red') . 'Could not make test data file. Reason: ['.$path.'] exists.' . $assist->ansii());
                        endif;

                    else:

                        // could not find test layout
                        Assist::out($assist->ansii('red') . 'Could not find layout file in ['.$testLayout.']' . $assist->ansii());

                    endif;

                endif;

            break;

            // generate doc
            case 'generate-doc':
            case 'make-doc':
            case 'doc':
                // this would generate a markdown for our tests
            break;

            // run unit or feature test
            case 'run':
                $argv = array_splice($argv, 1);
            default:

                if (\count($argv) > 0) :

                    // get test file
                    $testFile = \preg_replace('/[\s]+/', '', ucwords(\preg_replace('/[-]/', ' ', $argv[0])));

                    // @var bool $loadAll
                    $loadAll = \count($argv) == 1 ? true : false;

                    // check if test file exists
                    $path = self::$baseDirectory . '/' . $testFile . '.php';

                    // run test suite
                    if (file_exists($path)) :

                        // include test file
                        include_once $path;

                        // create instance
                        $instance = new $testFile;

                        // get all methods
                        $reflection = new \ReflectionClass($testFile);

                        // @var array $totalAssertions
                        $totalAssertions = [];

                        // get property
                        $assertions = $reflection->getProperty('assertions');

                        // load single
                        if ($loadAll === false) :

                            // get method
                            $method = (\property_exists($instance, 'triggers') && isset($instance->triggers[$argv[1]])) ? $instance->triggers[$argv[1]] : $argv[1];

                            // check if method exists
                            if (!\method_exists($instance, $method)) throw new MethodNotFound($testFile, $method);

                            // get arguments
                            $arguments = array_splice($argv, 2);

                            // load test method
                            self::loadTestMethod($instance, $method, $arguments, $assertions, $assist, $totalAssertions, $reflection);

                        else:

                            // get methods
                            $methods = $reflection->getMethods();

                            // load all
                            foreach ($methods as $method) :

                                // load if public   
                                if ($method->isPublic() && !$method->isStatic()) :

                                    // reset 
                                    $assertions->setValue([]);

                                    // load test method
                                    self::loadTestMethod($instance, $method->name, [], $assertions, $assist, $totalAssertions, $reflection);

                                endif;

                            endforeach;

                        endif;

                        // @var int $methods
                        $methods = 0;

                        // @var int $assertions
                        $assertions = 0;

                        // @var int $passed
                        $passed = 0;

                        // @var int $failed
                        $failed = 0;

                        // fetch assertions
                        foreach ($totalAssertions as $assertion) :

                            // update methods 
                            $methods++;

                            // update cases
                            $assertions += count($assertion);

                            // get passed and failed
                            foreach ($assertion as $returnVal) :

                                // assertion passed
                                if ($returnVal) $passed++;

                                // assertion failed
                                if (!$returnVal) $failed++;

                            endforeach;

                        endforeach;

                        // passed 
                        $testPassed = false;

                        // show summary
                        Assist::out($assist->ansii('bold') . '[Assertion Summary]' . $assist->ansii());

                        // show summary
                        $table = new Console_Table();
                        $table->setHeaders(['Method' . ($methods > 1 ? 's' : ''), 'Case' . ($assertions > 1 ? 's' : ''), 'Passed', 'Failed']);
                        $table->addRow([$methods, $assertions, $passed, $failed]);
                        $testPassed = $failed == 0 ? true : false;
                        Assist::out($table->getTable());
                        Assist::out('It appears that the test '
                            . ($testPassed == true ? 'was ' . ($assist->ansii('green') . 'successful.' . $assist->ansii()) :  ($assist->ansii('red') . 'failed.' . $assist->ansii())
                        ));
                        // it ends here

                    else:
                        // can not proceed, file does not exists
                        Assist::out($assist->ansii('red') . 'Could not run test suite. Reason: ['.$path.'] does not exist.' . $assist->ansii());
                    endif;

                endif;

        endswitch;

        // new line
        Assist::out(PHP_EOL);
    }

    /**
     * @method TestManager loadTestMethod
     * @param mixed $instance
     * @param string $method
     * @param array $arguments
     * @param mixed $assertions
     * @param Assist $assist
     * @param array $totalAssertions
     */
    private static function loadTestMethod($instance, string $method, array $arguments, $assertions, Assist $assist, array &$totalAssertions, $reflection) : void 
    {
        // log output
        Assist::out($assist->ansii('green') . 'Running test for {'.$method.'}' . $assist->ansii());

        // get failed
        $assertionFailed = $reflection->getProperty('assertionsFailed');

        // activate test environment
        if (!defined('TEST_ENVIRONMENT_ENABLED')) define('TEST_ENVIRONMENT_ENABLED', true);

        // set value 
        $assertionFailed->setValue([]);

        // load method
        call_user_func_array([$instance, $method], $arguments);

        // get assertions
        $assertions = $assertions->getValue();

        // get failed assertions
        $assertionFailed = $assertionFailed->getValue();

        // push 
        $totalAssertions[] = $assertions;

        // get passed
        $passed = 0;
        $failed = 0;
        $total = count($assertions);

        // get passed and failed
        foreach ($assertions as $response) :

            // assertion passed
            if ($response) $passed++;

            // assertion failed
            if (!$response) $failed++;

        endforeach;

        // show status
        Assist::sleep('Test suite ran for '. 
            $assist->ansii('green').'('.$total.')'.
            $assist->ansii() .
            ' case' . ($total > 1 ? 's' : '') . 
            ', '.$passed.' ' . $assist->ansii('green') . 'passed' . $assist->ansii() . 
            ', '.$failed.' ' . $assist->ansii('red') . 'failed' . $assist->ansii() . "\n"
        );

        if (count($assertionFailed) > 0) :

            // show error
            Assist::out($assist->ansii('red') . '=== Test Error ===' . $assist->ansii());  

            foreach ($assertionFailed as $index => $failed) :

                // get data
                $data = is_array($failed['data']) ? '[' . \implode(', ', $failed['data']) . ']' : $failed['data'];

                // manage object
                $data = \is_object($data) ? get_class($data) . '::class' : $data;

                // manage response
                $data = \preg_match('/[<](.*?>)/', $data) ? 'in ROUTE_REQUEST_RESPONSE' : 'with ' . $data;

                // print result
                Assist::out( ($index + 1)  . '. ' . $failed['condition'] . ', failed ' . $data);

            endforeach;

            // add new line
            Assist::out(PHP_EOL);

        endif;
    }

    /**
     * @method TestManager getBaseDirectory
     * @return string
     */
    public static function getBaseDirectory() : string 
    {
        return self::$baseDirectory;
    }
}