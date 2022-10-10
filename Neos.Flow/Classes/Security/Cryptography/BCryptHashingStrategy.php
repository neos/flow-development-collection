<?php
declare(strict_types=1);

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
 * Hashing passwords using BCrypt
 */
class BCryptHashingStrategy implements PasswordHashingStrategyInterface
{
    /**
     * Number of rounds to use with BCrypt for hashing passwords, must be between 4 and 31
     */
    protected int $cost;

    /**
     * Construct a BCrypt hashing strategy with the given parameters
     *
     * @param int $cost
     * @throws \InvalidArgumentException
     */
    public function __construct(int $cost)
    {
        if ($cost < 4 || $cost > 31) {
            throw new \InvalidArgumentException('BCrypt cost must be between 4 and 31.', 1318447710);
        }

        $this->cost = $cost;
    }

    /**
     * Creates a BCrypt hash
     *
     * @param string $password The plaintext password to hash
     * @param string|null $staticSalt Not used with this strategy
     * @return string The hashed password
     */
    public function hashPassword(string $password, string $staticSalt = null): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }

    /**
     * Validate a password against a derived key (hashed password) and salt using BCrypt
     *
     * Passwords hashed with a different cost can be validated by using the cost parameter of the
     * hashed password and salt.
     *
     * @param string $password The cleartext password
     * @param string $hashedPasswordAndSalt The derived key and salt in as returned by hashPassword() for verification
     * @param string|null $staticSalt Not used with this strategy
     * @return boolean true if the given password matches the hashed password
     */
    public function validatePassword(string $password, string $hashedPasswordAndSalt, string $staticSalt = null): bool
    {
        return password_verify($password, $hashedPasswordAndSalt);
    }
}
