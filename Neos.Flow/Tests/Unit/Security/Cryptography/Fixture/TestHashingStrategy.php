<?php
declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Cryptography\Fixture;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Cryptography\PasswordHashingStrategyInterface;

class TestHashingStrategy implements PasswordHashingStrategyInterface
{
    /**
     * @param string $password Cleartext password that will be hashed
     * @param string|null $staticSalt Optional static salt that will not be stored in the hashed password
     * @return string The hashed password with dynamic salt (if used)
     */
    public function hashPassword(string $password, string $staticSalt = null): string
    {
        return 'hashed' . $password . $staticSalt;
    }

    /**
     * @param string $password
     * @param string $hashedPasswordAndSalt Hashed password with dynamic salt (if used)
     * @param string|null $staticSalt Optional static salt that will not be stored in the hashed password
     * @return boolean true if the given cleartext password matched the hashed password
     */
    public function validatePassword(string $password, string $hashedPasswordAndSalt, string $staticSalt = null): bool
    {
        return false;
    }
}
