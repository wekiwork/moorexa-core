<?php
namespace Lightroom\Common;

use Exception;
use function Lightroom\Security\Functions\{
    encrypt, decrypt
};
/**
 * @package CsrfRequestManager
 * @author  Amadi Ifeanyi <amadiify.com>
 */
class CsrfRequestManager
{
    /**
     * @var string $default
     */
    private static $default = '';

    /**
     * @var CsrfRequestManager $instance
     */
    private static $instance;

    /**
     * @var array $handlerTriggers
     */
    private $handlerTriggers = [
        'loadFormCsrf' => 'verifyFormCsrf'
    ];

    /**
     * @var array $handlersError
     */
    private static $handlersError = [];

    /**
     * @var array $handlersVerification
     */
    private static $handlersVerification = [];

    /**
     * @method CsrfRequestManager __construct
     * @param string $default
     */
    public function __construct(string $default)
    {
        // set default 
        self::$default = $default;

        // update verification array
        self::$handlersVerification[$default] = false;

        // set instance
        self::$instance = $this;

        // include functions
        include_once __DIR__ . '/Functions.php';

        // load triggers
        if (isset($this->handlerTriggers[$default])) call_user_func([$this, $this->handlerTriggers[$default]]);
    }

    /**
     * @method CsrfRequestManager loadFormCsrf
     * @return string
     */
    public function loadFormCsrf() : string 
    {
        return '<input type="hidden" name="CSRF_TOKEN" value="'.$this->generateCsrfToken().'"/>';
    }

    /**
     * @method CsrfRequestManager verifyFormCsrf
     * @return void
     */
    public function verifyFormCsrf() : void 
    {
        if (isset($_POST['CSRF_TOKEN'])) :

            // @var string $token
            $token = decrypt($_POST['CSRF_TOKEN']);

            // remove token
            unset($_POST['CSRF_TOKEN']);

            // @var array $error
            $error = 'Invalid CSRF TOKEN';

            if (strlen($token) > 20) :
            
                // get session id
                $sessionId = session_id();

                // explode token
                $array = explode('/', $token);

                // build token with app url
                $builtToken = md5(func()->url($sessionId)) . 'salt:'.env('bootstrap', 'csrf_salt').'/'.$array[1].'/sessionId:'.$sessionId;

                // update error
                $error = 'CSRF TOKEN sent with this form has expired. Please resubmit form with the new token generated.';

                // now we verify
                if ($token == $builtToken) :
                
                    // regenerate id
                    session_regenerate_id();

                    // reset error
                    $error = '';

                    // update handler
                    self::$handlersVerification['loadFormCsrf'] = true;

                else:

                    // set header
                    http_response_code(400); // bad request

                endif;

                // add error
                self::$handlersError['loadFormCsrf'] = $error;

            endif;

            // empty post
            if ($error !== '') $_POST = [];

        endif;
    }

    /**
     * @method CsrfRequestManager generateCsrfToken
     * @return string
     */
    public function generateCsrfToken() : string 
    {
        // generate token
        $token = uniqid(time());

        // get session id
        $sessionId = session_id();

        // build token with app url
        $token = md5(func()->url($sessionId)) . 'salt:'.env('bootstrap', 'csrf_salt').'/token:'.$token.'/sessionId:'.$sessionId;

        // encrypt token with secret key
        return encrypt($token);
    }

    /**
     * @method CsrfRequestManager loadFromDefault
     * @return string
     * @throws Exception
     */
    public static function loadFromDefault() : string 
    {
        // throw exception
        if (self::$default == '') throw new Exception('No Default CSRF Method has been registered.');

        // load default
        return call_user_func([self::$instance, self::$default]);
    }

    /**
     * @method CsrfRequestManager loadDefaultError
     * @return string
     */
    public static function loadDefaultError() : string 
    {
        // @var string $default
        $default = self::$default;

        // @var string $error
        $error = '';
        
        // check for error
        if (isset(self::$handlersError[$default])) $error = self::$handlersError[$default];

        // return string
        return $error;
    }

    /**
     * @method CsrfRequestManager csrfVerified
     * @return bool
     */
    public static function csrfVerified() : bool 
    {
        return self::$handlersVerification[self::$default];
    }
}