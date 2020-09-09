<?php
namespace Lightroom\Security\Hashes;

use Exception;
use Lightroom\Adapter\Configuration\Environment;
use function Lightroom\Security\Functions\encrypt;

/**
 * @package SecureMd5
 * @author Amadi ifeanyi <amadiify.com>
 * 
 * This package adds an extra layer of security to the md5() hashing algorithm
 */
class SecureMd5
{
    /**
     * @method SecureMd5 generateKey
     * @param int $length
     * @return string
     * 
     * Generates a random key for md5()
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
     * @method SecureMd5 md5s
     * @param string $string
     * @param string $seed (optional)
     * @return string
     * @throws Exception
     */
    public static function md5s(string $string, string $seed = 'the seed is love!') : string 
    {
        // generate key and encrypt string
        return md5(self::generateKey() . encrypt($string, $seed));
    }
}