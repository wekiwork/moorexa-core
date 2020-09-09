<?php
namespace Lightroom\Packager\Moorexa;

use Lightroom\Security\{
    Hashes\PasswordHashing, SecurityGroupProperties, 
    Setter, Getter, Encrypt, Decrypt
};
use Lightroom\Security\Interfaces\{
    GetterInterface, SetterInterface, SecurityGroupInterface
};
/**
 * @package MoorexaSecurityGroup
 * @author Fregatelab <fregatelab.com>
 * 
 * The default security group for moorexa framework.
 * @method SetterInterface for
 */
class MoorexaSecurityGroup extends SecurityGroupProperties implements SecurityGroupInterface, GetterInterface, SetterInterface
{
    use Setter, Getter, Encrypt, Decrypt, PasswordHashing;

    /**
     * @method SecurityGroupInterface encryptString
     * @param string $string
     * @param string $seed
     * @return string
     * 
     * This method will encrypt a string and return an encrypted string
     */
    public function encryptString(string $string, string $seed = '') : string
    {
        return $this->encrypt($string, $seed);
    }

    /**
     * @method SecurityGroupInterface decryptString
     * @param string $encryptedString
     * @param string $seed
     * @return string
     * 
     * This method will decrypt a string and return an unencrypted string
     */
    public function decryptString(string $encryptedString, string $seed = '') : string
    {
        return $this->decrypt($encryptedString, $seed);
    }

    /**
     * @method SecurityGroupInterface hashAPassword
     * @param string $password
     * @param string $salt
     * @return string
     * 
     * This method should hash a password and return a string
     */
    public function hashAPassword(string $password, string $salt = '') : string
    {
        return $this->hashPassword($password, $salt);
    }

    /**
     * @method SecurityGroupInterface verifyAPassword
     * @param string $password
     * @param string $hashedString
     * @param string $salt
     * @return bool This method should verify a hashed password and return a true if valid or false if not.
     *
     * This method should verify a hashed password and return a true if valid or false if not.
     */
    public function verifyAPassword(string $password, string $hashedString, string $salt = '') : bool
    {
        return $this->verifyPassword($password, $hashedString, $salt);
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement @method SetterInterface for
    }
}