<?php
namespace Lightroom\Security\Interfaces;

interface GetterInterface
{
    /**
     * @method GetterInterface getSecurityKey
     * This method returns the security key
     * @return string
     */
    public function getSecurityKey() : string ;

    /**
     * @method GetterInterface getSecurityKeySalt
     * This method returns the security key Salt
     * @return string
     */
    public function getSecurityKeySalt() : string ;

    /**
     * @method GetterInterface getEncryptionMethod
     * This method returns an encryption method
     * @return string
     */
    public function getEncryptionMethod() : string ;

    /**
     * @method GetterInterface getEncryptionSalt
     * This method returns an encryption salt
     * @return string
     */
    public function getEncryptionSalt() : string ;

    /**
     * @method GetterInterface getEncryptionCertificate
     * This method returns an encryption certificate
     * @return string
     */
    public function getEncryptionCertificate() : string ;
}