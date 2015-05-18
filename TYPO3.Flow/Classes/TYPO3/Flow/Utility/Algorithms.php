<?php
namespace TYPO3\Flow\Utility;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use RandomLib\Factory;
use RandomLib\Generator;
use Ramsey\Uuid\Uuid;
use TYPO3\Flow\Annotations as Flow;

/**
 * A utility class for various algorithms.
 *
 * @Flow\Scope("singleton")
 */
class Algorithms
{
    /**
     * @var Factory
 */
    protected static $randomGeneratorFactory;

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
        $generator = static::getRandomGeneratorFactory()->getMediumStrengthGenerator();
        return $generator->generate($count);
    }

    /**
     * Returns a random token in hex format.
     *
     * @param integer $count Token length
     * @return string A random token
     */
    public static function generateRandomToken($count)
    {
        $generator = static::getRandomGeneratorFactory()->getMediumStrengthGenerator();
        return $generator->generateString($count * 2, Generator::CHAR_LOWER_HEX);
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
        $generator = static::getRandomGeneratorFactory()->getMediumStrengthGenerator();
        return $generator->generateString($count, $characters);
    }

    /**
     * Gets an instance of the RandomLib\Factory to avoid initialization costs.
     *
     * @return Factory
     */
    protected static function getRandomGeneratorFactory()
    {
        if (static::$randomGeneratorFactory === null) {
            static::$randomGeneratorFactory = new Factory();
        }

        return static::$randomGeneratorFactory;
    }
}
