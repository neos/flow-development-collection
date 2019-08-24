<?php
declare(strict_types=1);

namespace Neos\Flow\Utility;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Unicode\Functions;
use Ramsey\Uuid\Uuid;
use Neos\Flow\Annotations as Flow;

/**
 * A utility class for various algorithms.
 *
 * @Flow\Scope("singleton")
 */
class Algorithms
{
    /**
     * Generates a universally unique identifier (UUID) according to RFC 4122.
     * The algorithm used here, might not be completely random.
     *
     * If php-uuid was installed it will be used instead to speed up the process.
     *
     * @return string The universally unique id
     * @throws \Exception
     * @todo Optionally generate type 1 and type 5 UUIDs.
     */
    public static function generateUUID(): string
    {
        if (is_callable('uuid_create')) {
            return strtolower(uuid_create(UUID_TYPE_RANDOM));
        }

        return (string)Uuid::uuid4();
    }

    /**
     * Returns a string of random bytes.
     *
     * @param int $count Number of bytes to generate
     * @return string Random bytes
     * @throws \Exception
     */
    public static function generateRandomBytes(int $count): string
    {
        return random_bytes($count);
    }

    /**
     * Returns a random token in hex format.
     *
     * @param int $count Token length
     * @return string A random token
     * @throws \Exception
     */
    public static function generateRandomToken(int $count): string
    {
        return bin2hex(random_bytes($count));
    }

    /**
     * Returns a random string with alpha-numeric characters.
     *
     * @param integer $count Number of characters to generate
     * @param string $characters Allowed characters, defaults to alpha-numeric (a-zA-Z0-9)
     * @return string A random string
     * @throws \Exception
     */
    public static function generateRandomString(int $count, string $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'): string
    {
        $characterCount = Functions::strlen($characters);
        $string = '';
        for ($i = 0; $i < $count; $i++) {
            $string .= Functions::substr($characters, random_int(0, ($characterCount - 1)), 1);
        }

        return $string;
    }
}
