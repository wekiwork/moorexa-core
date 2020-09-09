<?php
namespace Lightroom\Security\Interfaces;

/**
 * @method SetterInterface for security group
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab>
 */
interface SetterInterface
{
    /**
     * @method SetterInterface setSecurityKey
     * This method sets a security key
     * @param string $key
     * @return void
     */
    public function setSecurityKey(string $key) : void;

    /**
     * @method SetterInterface setSecurityKeySalt
     * This method sets a security key Salt
     * @param string $salt
     * @return void
     */
    public function setSecurityKeySalt(string $salt) : void;

    /**
     * @method SetterInterface setEncryptionMethod
     * This method sets an encryption method
     * @param string $method
     * @return void
     */
    public function setEncryptionMethod(string $method) : void ;

    /**
     * @method SetterInterface setEncryptionSalt
     * This method sets an encryption salt
     * @param string $salt
     * @return void
     */
    public function setEncryptionSalt(string $salt) : void ;

    /**
     * @method SetterInterface setEncryptionCertificate
     * This method sets an encryption certificate
     * @param string $filepath
     * @return void
     */
    public function setEncryptionCertificate(string $filepath) : void ;
}