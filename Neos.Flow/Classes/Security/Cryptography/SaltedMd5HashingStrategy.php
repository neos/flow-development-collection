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
 * A salted MD5 based password hashing strategy
 *
 */
class SaltedMd5HashingStrategy implements PasswordHashingStrategyInterface
{
    /**
     * Generates a salted md5 hash over the given string.
     *
     * @param string $clearString The unencrypted string which is the subject to be hashed
     * @return string Salted hash and the salt, separated by a comma ","
     */
    public static function generateSaltedMd5($clearString)
    {
        $salt = substr(md5(uniqid(rand(), true)), 0, rand(6, 10));
        return (md5(md5($clearString) . $salt) . ',' . $salt);
    }

    /**
     * Tests if the given string would produce the same hash given the specified salt.
     * Use this method to validate hashes generated with generateSlatedMd5().
     *
     * @param string $clearString
     * @param string $hashedStringAndSalt
     * @return boolean TRUE if the clear string matches, otherwise FALSE
     * @throws \InvalidArgumentException
     */
    public static function validateSaltedMd5($clearString, $hashedStringAndSalt)
    {
        if (strpos($hashedStringAndSalt, ',') === false) {
            throw new \InvalidArgumentException('The hashed string must contain a salt, separated with comma from the hashed.', 1269872776);
        }
        list($passwordHash, $salt) = explode(',', $hashedStringAndSalt);
        return (md5(md5($clearString) . $salt) === $passwordHash);
    }

    /**
     * Hash a password using salted MD5
     *
     * @param string $password The cleartext password
     * @param string $staticSalt ignored parameter
     * @return string A hashed password with salt
     */
    public function hashPassword($password, $staticSalt = null)
    {
        return self::generateSaltedMd5($password);
    }

    /**
     * Validate a hashed password using salted MD5
     *
     * @param string $password The cleartext password
     * @param string $hashedPasswordAndSalt The hashed password with salt
     * @param string $staticSalt ignored parameter
     * @return boolean TRUE if the given password matches the hashed password
     */
    public function validatePassword($password, $hashedPasswordAndSalt, $staticSalt = null)
    {
        return self::validateSaltedMd5($password, $hashedPasswordAndSalt);
    }
}
