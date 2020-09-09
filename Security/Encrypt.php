<?php
namespace Lightroom\Security;

/**
 * @package Encrypt trait
 * @author Amadi ifeanyi <amadiify.com>
 * This package encrypts a string and returns an encrypted string. 
 * This trait should be used on a class that implements the GetterInterface for security group
 */
trait Encrypt
{
    /**
     * @var int $encryptionLevel
     * 
     * Should be equivalent to decrypt encryptionLevel
     */
    private $encryptionLevel = 2;

    /**
    * OpenSSL AES encryption for strings
    * @method encrypt()
    * @param string $string
    * @param string $seed (an extra key or pass code that makes string decomposable only by it)
    * @return string
    */
    public function encrypt(string $string, string $seed='') : string
    {
        // get certificate
        $certificate = $this->getEncryptionCertificate();

        // get security key, append $seed and certificate
        $secretKey = hash('sha256', $this->getSecurityKey() . $seed . $certificate);

        // get secret key salt
        $secret_iv = $this->getSecurityKeySalt() . $seed . $certificate;

        // encrypt data;
        $encrypted = openssl_encrypt($string, $this->getEncryptionMethod(), $secretKey, 0, substr(hash('sha256', $secret_iv), 0, 16));

        // encrypt data and return string
        return base64_encode($this->encryptNeedsRehash($encrypted, $this->encryptionLevel));
    }

    /**
    * @method encryptNeedsRehash() to re-encrypt data
    * @param string $encrypted
    * @param int $level (encryption level)
    * @return string 
    */
    private function encryptNeedsRehash(string $encrypted, int $level) : string
    {
        // serialize and reverse string at every level
        $encryptedString = serialize(strrev($encrypted));

        // IF level is not zero, add an extra layer
        if ($level != 0) :
        
            // decrease level by 1
            $level -= 1;

            // encrypt string again
            $encryptedString = $this->encryptNeedsRehash($encryptedString, $level);

        endif;

        // return string
        return $encryptedString;
    }
}