<?php
namespace Lightroom\Security;

use Lightroom\Common\File;
use Lightroom\Adapter\Configuration\Environment;

/**
 * @package Getter trait for security group
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
trait Getter
{
    /**
     * @method SecurityGroup getSecurityKey
     * This method returns the security key
     * @return string
     */
    public function getSecurityKey() : string 
    {
        // return from environment
        // this secret key can be found in the configuration file
        return $this->securityKey != '' ? $this->securityKey : Environment::getEnv('bootstrap', 'secret_key');
    }

    /**
     * @method SecurityGroup getSecurityKeySalt
     * This method returns the security key Salt
     * @return string
     */
    public function getSecurityKeySalt() : string 
    {
        // return from environment
        // this secret key can be found in the configuration file
        return $this->securityKeySalt != '' ? $this->securityKeySalt : Environment::getEnv('bootstrap', 'secret_key_salt');
    }

    /**
     * @method SecurityGroup getEncryptionMethod
     * This method returns an encryption method
     * @return string
     */
    public function getEncryptionMethod() : string 
    {
        // return from environment
        // this encryption method can be found in the configuration file
        return $this->encryptionMethod != '' ? $this->encryptionMethod : Environment::getEnv('bootstrap', 'encryption.method');
    }

    /**
     * @method SecurityGroup getEncryptionSalt
     * This method returns an encryption salt
     * @return string
     */
    public function getEncryptionSalt() : string 
    {
        // salt file path
        $filepath = $this->encryptionSalt != '' ? $this->encryptionSalt : __DIR__ . '/../Security/Salts/8bitSaltedString.key';

        // return from salts or use fallback salt
        return File::read($filepath, $this->fallbackSalt);
    }

    /**
     * @method SecurityGroup getEncryptionCertificate
     * This method returns an encryption certificate
     * @return string
     */
    public function getEncryptionCertificate() : string 
    {
        // salt file path
        $filepath = $this->encryptionCertificate != '' ? $this->encryptionCertificate : __DIR__ . '/../Security/Certificates/certificate.key';

        // return from salts or use fallback salt
        return File::read($filepath, $this->fallbackSalt);
    }
}