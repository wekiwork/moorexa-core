<?php
namespace Lightroom\Core;

use Lightroom\Adapter\{
    Configuration\Interfaces\URLInterface, URL
};
use Lightroom\Common\Interfaces\RequirementInterface;
/**
 * @package Framework Requirements
 * @author fregatelab <fregatelab.com>
 * @author Amadi Ifeanyi <amadiify.com>
 */

class FrameworkRequirements implements RequirementInterface, URLInterface
{
    use URL;

    /**
     * @var $requirements
     */
    private $requirements = [
        'phpversion' => [FrameworkRequirements::class, 'checkPhpVersion'],
        'pdo'        => [FrameworkRequirements::class, 'checkPdoExtension'],
        'openssl'    => [FrameworkRequirements::class, 'checkOpenSSLExtension'],
        'curl'       => [FrameworkRequirements::class, 'checkCurlExtension'],
    ];

    /**
     * @var $requirement_errors
     */
    private $requirement_errors = [];

    /**
     * @method FrameworkRequirements loadAll
     * @return array
     */
    public function loadAll() : array
    {
        return $this->requirements;
    }

    /**
     * @method FrameworkRequirements requirementFailed
     * @param array $failed
     * @return null
     */
    public function requirementFailed(array $failed)
    {
        $content = file_get_contents(PATH_TO_SYSTEM . "/Starter/default-starter.html");

        $str = preg_quote('requirement');
        $content = str_replace('--font', $this->getPathUrl() . PATH_TO_ASSETS . '/fonts/HelveticaNeueUltraLight.ttf', $content);
        $content = str_replace('--text', $this->getPathUrl() . PATH_TO_ASSETS . '/fonts/Poppins-Regular.ttf', $content);
        preg_match("/(<!)[-]+\s*(@$str)\s*[-]*[>]/", $content, $match);
        preg_match("/(<!)[-]+\s*(@end$str)\s*[-]*[>]/", $content, $match2);
        $start = $match[0];
        $end = $match2[0];

        $begin = strstr($content, $start);
        $string = substr($begin, 0, strpos($begin, $end));

        $body = '';

        if (count($failed) > 0) :
        
            foreach($failed as $key => $error) :
            
                $body .= '<tr>';
                $body .= '<td>'.ucfirst($key).'</td>';
                $body .= '<td>'.$error.'</td>';
                $body .= '</tr>';

                // clean up
                unset($key, $error);

            endforeach;
        
        endif;

        // clean up
        unset($failed, $str, $content, $match, $match2, $start, $end, $begin);
        
        // echo exception template body
        echo str_replace('{table-data}', $body, $string);
    }

    /**
     * @method FrameworkRequirements setMethod
     * set requirement error
     * @param string $requirementName
     * @param string $error
     */
    public function setError(string $requirementName, string $error)
    {
        $this->requirement_errors[$requirementName] = $error;
    }

    /**
     * @method FrameworkRequirements getClass
     * get requirement error
     * @param string $requirement
     * @return string
     */
    public function getError(string $requirement) : string
    {
        $error = '';

        if (isset($this->requirement_errors[$requirement]))

            // get error from requirements
            $error = $this->requirement_errors[$requirement];
        

        return $error;
    }

    /**
     * @method FrameworkRequirements checkPhpVersion
     * @return bool
     */
    public function checkPhpVersion() : bool
    {
        // set required version
        $version = '^7.2.0'; // supported sign (^ or ~)

        // get current version
        $currentVersion = floatval(phpversion());

        // version check passed
        $versionPassed = false;

        // high
        if (strpos($version, '^') !== false)

            // version passed
            $versionPassed = ($currentVersion >= floatval(str_replace('^', '', $version))) ? true : false;


        // low or equal
        if (strpos($version, '~') !== false)

            // version passed
            $versionPassed = ($currentVersion <= floatval(str_replace('~', '', $version))) ? true : false;


        // set error
        if ($versionPassed === false)

            $this->setError('phpversion', 'Your current PHP version <b>'.phpversion().'</b> is less than the required version <b>' . $version . '</b>');

        
        // clean up
        unset($version, $currentVersion);

        // return bool
        return $versionPassed;
    }

    /**
     * @method FrameworkRequirements checkPdoExtension
     * @return bool
     */
    public function checkPdoExtension() : bool
    {
        $pdoPassed = false;        

        // check for pdo class
        if (class_exists('PDO') && class_exists('PDOException'))

            // check passed
            $pdoPassed = true;

        
        if (!$pdoPassed)

            $this->setError('pdo', 'PDO Class Missing. Please ensure to install it and restart server.');


        return $pdoPassed;
    }

    /**
     * @method FrameworkRequirements checkOpenSSLExtension
     * @return bool
     */
    public function checkOpenSSLExtension()
    {
        // openssl passed
        $opensslPassed = false;

        if (function_exists('openssl_encrypt'))

            // check passed
            $opensslPassed = true;

        
        if (!$opensslPassed)

            $this->setError('openssl', 'PHP OpenSSL Extension missing.');

        // return bool
        return $opensslPassed;
    }

    /**
     * @method FrameworkRequirements checkCurlExtension
     * @return bool
     */
    public function checkCurlExtension()
    {
         // curl passed
         $curlPassed = false;

         if (function_exists('curl_init'))
 
             // check passed
             $curlPassed = true;
 
         
         if (!$curlPassed)
 
             $this->setError('curl', 'PHP CURL Extension missing.');
 
         // return bool
         return $curlPassed;
    }
}