<?php
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
     * @todo Optionally generate type 1 and type 5 UUIDs.
     */
    public static function generateUUID()
    {
        if (is_callable('uuid_create')) {
            return strtolower(uuid_create(UUID_TYPE_RANDOM));
        }

        return (string)Uuid::uuid4();
    }

    /**
     * Returns a string of random bytes.
     *
     * @param integer $count Number of bytes to generate
     * @return string Random bytes
     */
    public static function generateRandomBytes($count)
    {
        return random_bytes($count);
    }

    /**
     * Returns a random token in hex format.
     *
     * @param integer $count Token length
     * @return string A random token
     */
    public static function generateRandomToken($count)
    {
        return bin2hex(random_bytes($count));
    }

    /**
     * Returns a random string with alpha-numeric characters.
     *
     * @param integer $count Number of characters to generate
     * @param string $characters Allowed characters, defaults to alpha-numeric (a-zA-Z0-9)
     * @return string A random string
     */
    public static function generateRandomString($count, $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        $characterCount = \Neos\Utility\Unicode\Functions::strlen($characters);
        $string = '';
        for ($i = 0; $i < $count; $i++) {
            $string .= \Neos\Utility\Unicode\Functions::substr($characters, random_int(0, ($characterCount - 1)), 1);
        }

        return $string;
    }
}
