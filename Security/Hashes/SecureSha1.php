<?php
namespace Lightroom\Security\Hashes;

use Exception;
use Lightroom\Adapter\Configuration\Environment;
use function Lightroom\Security\Functions\encrypt;

/**
 * @package SecureSha1
 * @author Amadi ifeanyi <amadiify.com>
 * 
 * This package adds an extra layer of security to the sha1() hashing algorithm
 */
class SecureSha1
{
    /**
     * @method SecureSha1 generateKey
     * @param int $length
     * @return string
     * 
     * Generates a random key for sha1()
     */
    private static function generateKey(int $length = 5) : string
    {
        // is secure
        $isSecure = null;

        // generate a random key
        $randomKey = openssl_random_pseudo_bytes($length, $isSecure);

        // return created key
        return hash('sha256', $randomKey . time() . Environment::getEnv('bootstrap', 'secret_key'));
    }


    /**
     * @method SecureSha1 sha1s
     * @param string $string
     * @param string $seed (optional)
     * @return string
     * @throws Exception
     */
    public static function sha1s(string $string, string $seed = 'the seed is love!') : string 
    {
        // generate key and encrypt string
        return sha1(self::generateKey() . encrypt($string, $seed));
    }
}