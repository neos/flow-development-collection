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
 * A password hashing strategy interface
 *
 */
interface PasswordHashingStrategyInterface
{
    /**
     * Hash a password for storage
     *
     * @param string $password Cleartext password that will be hashed
     * @param string $staticSalt Optional static salt that will not be stored in the hashed password
     * @return string The hashed password with dynamic salt (if used)
     */
    public function hashPassword($password, $staticSalt = null);

    /**
     * Validate a hashed password against a cleartext password
     *
     * @param string $password
     * @param string $hashedPasswordAndSalt Hashed password with dynamic salt (if used)
     * @param string $staticSalt Optional static salt that will not be stored in the hashed password
     * @return boolean TRUE if the given cleartext password matched the hashed password
     */
    public function validatePassword($password, $hashedPasswordAndSalt, $staticSalt = null);
}
