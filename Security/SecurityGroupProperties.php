<?php
namespace Lightroom\Security;

/**
 * @package Security Group properties
 * @author Amadi Ifeanyi <amadiify.com>
 */
class SecurityGroupProperties
{
    /**
     * @var string $securityKey
     */
    protected $securityKey = '';

    /**
     * @var string $securityKeySalt
     */
    protected $securityKeySalt = '';

    /**
     * @var string $encryptionMethod
     */
    protected $encryptionMethod = '';

    /**
     * @var string $encryptionSalt
     */
    protected $encryptionSalt = '';

    /**
     * @var string $encryptionCertificate
     */
    protected $encryptionCertificate = '';

     /**
     * @var string $fallbackSalt
     */
    protected $fallbackSalt = '';
}
