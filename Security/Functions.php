<?php
namespace Lightroom\Security\Functions;

use Lightroom\Security\{Decrypt, Encrypt, Hashes\PasswordHashing, SecurityGroup, Hashes\SecureMd5, Hashes\SecureSha1};
use Exception;

/**
 * @method Encrypt encrypt string
 * @param string $string
 * @param string $seed (optional)
 * @return string
 * @throws Exception
 */
function encrypt(string $string, string $seed = '') : string
{
    // encrypt string
    return SecurityGroup::getDefault()->encryptString($string, $seed);
}

/**
 * @method Decrypt decrypt string
 * @param string $encryptedString
 * @param string $seed (optional, but required if a seed was added to the encrypt method)
 * @return string
 * @throws Exception
 */
function decrypt(string $encryptedString, string $seed = '') : string
{
    // decrypt string
    return SecurityGroup::getDefault()->decryptString($encryptedString, $seed);
}

/**
 * @method SecureMd5 md5s string with md5 secure
 * @param string $string
 * @param string $seed (optional)
 * @return string
 */
function md5s(string $string, string $seed = '') : string
{
    // hash string
    return SecureMd5::md5s($string, $seed);
}

/**
 * @method SecureSha1 sha1s string with sha1 secure
 * @param string $string
 * @param string $seed (optional)
 * @return string
 */
function sha1s(string $string, string $seed = '') : string
{
    // hash string
    return SecureSha1::sha1s($string, $seed);
}

/**
 * @method PasswordHashing hash_password
 * @param string $password
 * @param string $salt
 * @return string
 * @throws Exception
 */
function hash_password(string $password, string $salt = '') : string
{
    // hash password
    return SecurityGroup::getDefault()->hashAPassword($password, $salt);
}

/**
 * @method PasswordHashing verify_password
 * @param string $password
 * @param string $hashedPassword
 * @param string $salt
 * @return bool
 * @throws Exception
 */
function verify_password(string $password, string $hashedPassword, string $salt = '') : bool
{
    // hash password
    return SecurityGroup::getDefault()->verifyAPassword($password, $hashedPassword, $salt);
}
