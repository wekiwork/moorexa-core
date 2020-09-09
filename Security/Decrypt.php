<?php
namespace Lightroom\Security;

/**
 * @package Decrypt trait
 * @author Amadi ifeanyi <amadiify.com>
 * This package decrypts a string and returns a plain string. 
 * This trait should be used on a class that implements the GetterInterface for security group
 */
trait Decrypt
{
    /**
     * @var int $encryptionLevel
     * 
     * Should be equivalent to encrypt encryptionLevel
     */
    private $encryptionLevel = 2;

    /**
    * OpenSSL AES decryption for strings
    * @method decrypt()
    * @param string $encryptedString
    * @param string $seed (an extra key or pass code that was used when encrypting string to make it decomposable)
    * @return string
    */
    public function decrypt(string $encryptedString, string $seed='') : string
    {
        // try decrypt string from base64_encode
        $encryptedString = $this->decryptFromBase64(base64_decode($encryptedString), $this->encryptionLevel);

        // get certificate
        $certificate = $this->getEncryptionCertificate();

        // get secret key, append $seed and certificate
        $secretKey = hash('sha256', $this->getSecurityKey() . $seed . $certificate);

        // get secret key salt
        $secret_iv = $this->getSecurityKeySalt() . $seed . $certificate;

        // decrypt data;
        return openssl_decrypt($encryptedString, $this->getEncryptionMethod(), $secretKey, 0, substr(hash('sha256', $secret_iv), 0, 16));
    }

    /**
    * @method decryptFromBase64() to decrypt data from base64 using the encryption level
    * @param string $base64string
    * @param int $level (encryption level)
    * @return string 
    */
    private function decryptFromBase64(string $base64string, int $level) : string
    {
         // reverse and unserializable string at every level
         $encryptedString = strrev(unserialize($base64string));

         // IF level is not zero, add an extra layer
         if ($level != 0) :
         
             // decrease level by 1
             $level -= 1;

             // encrypt string again
             $encryptedString = $this->decryptFromBase64($encryptedString, $level);

         endif;

         // return string
         return $encryptedString;
    }
}