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

use Neos\Flow\Utility\Algorithms as UtilityAlgorithms;

/**
 * A PBKDF2 based password hashing strategy
 *
 */
class Pbkdf2HashingStrategy implements PasswordHashingStrategyInterface
{
    /**
     * Length of the dynamic random salt to generate in bytes
     */
    protected int $dynamicSaltLength;

    /**
     * Hash iteration count, high counts (>10.000) make brute-force attacks unfeasible
     */
    protected int $iterationCount;

    /**
     * Derived key length
     */
    protected int $derivedKeyLength;

    /**
     * Hash algorithm to use, see hash_algos()
     */
    protected string $algorithm;

    /**
     * Construct a PBKDF2 hashing strategy with the given parameters
     *
     * @param integer $dynamicSaltLength Length of the dynamic random salt to generate in bytes
     * @param integer $iterationCount Hash iteration count, high counts (>10.000) make brute-force attacks unfeasible
     * @param integer $derivedKeyLength Derived key length
     * @param string $algorithm Hash algorithm to use, see hash_algos()
     */
    public function __construct(int $dynamicSaltLength, int $iterationCount, int $derivedKeyLength, string $algorithm)
    {
        $this->dynamicSaltLength = $dynamicSaltLength;
        $this->iterationCount = $iterationCount;
        $this->derivedKeyLength = $derivedKeyLength;
        $this->algorithm = $algorithm;
    }

    /**
     * Hash a password for storage using PBKDF2 and the configured parameters.
     * Will use a combination of a random dynamic salt and the given static salt.
     *
     * @param string $password Cleartext password that should be hashed
     * @param string|null $staticSalt Static salt that will be appended to the random dynamic salt
     * @return string A Base64 encoded string with the derived key (hashed password) and dynamic salt
     */
    public function hashPassword(string $password, string $staticSalt = null): string
    {
        $dynamicSalt = UtilityAlgorithms::generateRandomBytes($this->dynamicSaltLength);
        $result = hash_pbkdf2($this->algorithm, $password, $dynamicSalt . $staticSalt, $this->iterationCount, $this->derivedKeyLength, true);
        return base64_encode($dynamicSalt) . ',' . base64_encode($result);
    }

    /**
     * Validate a password against a derived key (hashed password) and salt using PBKDF2.
     * Iteration count and algorithm have to match the parameters when generating the derived key.
     *
     * @param string $password The cleartext password
     * @param string $hashedPasswordAndSalt The derived key and salt in Base64 encoding as returned by hashPassword for verification
     * @param string|null $staticSalt Static salt that will be appended to the dynamic salt
     * @return bool true if the given password matches the hashed password
     * @throws \InvalidArgumentException
     */
    public function validatePassword(string $password, string $hashedPasswordAndSalt, string $staticSalt = null): bool
    {
        $parts = explode(',', $hashedPasswordAndSalt);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('The derived key with salt must contain a salt, separated with a comma from the derived key', 1306172911);
        }
        $dynamicSalt = base64_decode($parts[0]);
        $derivedKey = base64_decode($parts[1]);
        $derivedKeyLength = strlen($derivedKey);
        return $derivedKey === hash_pbkdf2($this->algorithm, $password, $dynamicSalt . $staticSalt, $this->iterationCount, $derivedKeyLength, true);
    }
}
