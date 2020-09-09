<?php
namespace Lightroom\Security;

use Lightroom\Security\Interfaces\SetterInterface;

/**
 * @package Security Group setter methods
 * @author Amadi ifeanyi <amadiify.com>
 */
trait Setter
{
    /**
     * @method SetterInterface setSecurityKey
     * This method sets a security key
     * @param string $key
     * @return void
     */
    public function setSecurityKey(string $key) : void
    {
        $this->securityKey = $key;
    }

    /**
     * @method SetterInterface setSecurityKeySalt
     * This method sets a security key Salt
     * @param string $salt
     * @return void
     */
    public function setSecurityKeySalt(string $salt) : void
    {
        $this->securityKeySalt = $salt;
    }

    /**
     * @method SetterInterface setEncryptionMethod
     * This method sets an encryption method
     * @param string $method
     * @return void
     */
    public function setEncryptionMethod(string $method) : void 
    {
        $this->encryptionMethod = $method;
    }

    /**
     * @method SetterInterface setEncryptionSalt
     * This method sets an encryption salt
     * @param string $salt
     * @return void
     */
    public function setEncryptionSalt(string $salt) : void 
    {
        $this->encryptionSalt = $salt;
    }

    /**
     * @method SetterInterface setEncryptionCertificate
     * This method sets an encryption certificate
     * @param string $filepath
     * @return void
     */
    public function setEncryptionCertificate(string $filepath) : void 
    {
        $this->encryptionCertificate = $filepath;
    }
}