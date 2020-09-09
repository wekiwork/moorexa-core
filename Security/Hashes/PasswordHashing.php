<?php
namespace Lightroom\Security\Hashes;

use Lightroom\Adapter\Configuration\Environment;
use Lightroom\Common\File;

/**
 * @package Password hashing mechanism
 * @author Amadi Ifeanyi
 * 
 * This package hashes a password and verifies a password against an hashed password
 */
trait PasswordHashing
{
    /**
     * @var string $hashedValue
     */
    private $hashedValue = '';


    /**
     * @method PasswordHashing hashPassword
     * @param string $password
     * @param string $salt
     *
     * This method hashes a password
     * @return string
     */
    public function hashPassword(string $password, string $salt = '') : string 
    {
        // generate password salt
        $passwordSalt = hash('sha256', md5($password) . '\\' . $this->getSecurityKey() . '\\' . $this->getEncryptionCertificate());

        // get password strength
        $length = strlen($password);
        $length = $length > 26 ? 26 : $length;

        // add extra characters
        $char = range('A','Z');

        // add digits
        $digits = range(1, ($length == 1 ? 2 : $length));

        // create a salt string
        $string = '$'.implode("", array_splice($char, 0, $length)) . '$' . implode("", array_splice($digits, 0, $length));

        // new value
        $value = sha1($string.'$'.$password . '@appsalt:'. $passwordSalt . '@devsalt:' . $salt);

        // update hashed value
        $this->hashedValue = $value;

        // hash value with password_hash or crypt
        if (function_exists('password_hash')) :
        
            return password_hash($value, PASSWORD_BCRYPT);
        
        endif;

        return crypt($value, CRYPT_BLOWFISH);
    }

    /**
     * @method PasswordHashing verifyPassword
     * @param string $plainString
     * @param string $hashedPassword
     * @param string $salt
     * @return bool
     */

    public function verifyPassword(string $plainString, string $hashedPassword, string $salt = '') : bool
    {
        // hash plain string
        $hashedPlain = $this->hashPassword($plainString, $salt);

        // password valid
        $passwordValid = ($hashedPassword == $hashedPlain) ? true : false;

        // use password verify
        if (function_exists('password_verify')) :
        
            if (password_verify($this->hashedValue, $hashedPassword)) :
            
                // password valid
                $passwordValid = true;
            
            endif;
        
        endif;

        // return bool
        return $passwordValid;
    }
}