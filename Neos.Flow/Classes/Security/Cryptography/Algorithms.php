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
 * @deprecated since 8.2, use PHPs `hash_pbkdf2`
 */
class Algorithms
{
    /**
     * Compute a derived key from a password based on PBKDF2
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
        return hash_pbkdf2($algorithm, $password, $salt, $iterationCount, $derivedKeyLength, true);
    }
}
