<?php
namespace Neos\Flow\Security\Cryptography;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Cryptographic algorithms
 *
 * Right now this class provides a PHP based PBKDF2 implementation.
 *
 */
class Algorithms
{
    /**
     * Compute a derived key from a password based on PBKDF2
     *
     * See PKCS #5 v2.0 http://tools.ietf.org/html/rfc2898 for implementation details.
     * The implementation is tested with test vectors from http://tools.ietf.org/html/rfc6070 .
     *
     * If https://wiki.php.net/rfc/hash_pbkdf2 is ever part of PHP we should check for the
     * existence of hash_pbkdf2() and use it if available.
     *
     * @param string $password Input string / password
     * @param string $salt The salt
     * @param integer $iterationCount Hash iteration count
     * @param integer $derivedKeyLength Derived key length
     * @param string $algorithm Hash algorithm to use, see hash_algos(), defaults to sha256
     * @return string The computed derived key as raw binary data
     */
    public static function pbkdf2($password, $salt, $iterationCount, $derivedKeyLength, $algorithm = 'sha256')
    {
        $hashLength = strlen(hash($algorithm, null, true));
        $keyBlocksToCompute = ceil($derivedKeyLength / $hashLength);
        $derivedKey = '';

        for ($block = 1; $block <= $keyBlocksToCompute; $block++) {
            $iteratedBlock = hash_hmac($algorithm, $salt . pack('N', $block), $password, true);

            for ($iteration = 1, $iteratedHash = $iteratedBlock; $iteration < $iterationCount; $iteration++) {
                $iteratedHash = hash_hmac($algorithm, $iteratedHash, $password, true);
                $iteratedBlock ^= $iteratedHash;
            }

            $derivedKey .= $iteratedBlock;
        }

        return substr($derivedKey, 0, $derivedKeyLength);
    }
}
