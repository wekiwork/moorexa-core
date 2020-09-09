<?php
namespace Lightroom\Security\Interfaces;

/**
 * @package security group interface
 * @author Amadi ifeanyi <amadiify.com>
 */
interface SecurityGroupInterface
{
    /**
     * @method SecurityGroupInterface encryptString
     * @param string $string
     * @param string $seed
     * @return string
     * 
     * This method should encrypt a string and return an encrypted string
     */
    public function encryptString(string $string, string $seed = '') : string;   

    /**
     * @method SecurityGroupInterface decryptString
     * @param string $encryptedString
     * @param string $seed
     * @return string
     * 
     * This method should decrypt a string and return an unencrypted string
     */
    public function decryptString(string $encryptedString, string $seed = '') : string;  
    
    /**
     * @method SecurityGroupInterface hashAPassword
     * @param string $password
     * @param string $salt
     * @return string
     * 
     * This method should hash a password and return a string
     */
    public function hashAPassword(string $password, string $salt = '') : string;
    
     /**
     * @method SecurityGroupInterface verifyAPassword
     * @param string $password
     * @param string $hashedString
     * @param string $salt
     * @return string
     * 
     * This method should verify a hashed password and return a true if valid or false if not.
     */
    public function verifyAPassword(string $password, string $hashedString, string $salt = '') : bool;
}