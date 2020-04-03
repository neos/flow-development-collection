<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\Flow\Security\Authentication;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class CredentialsSource implements \JsonSerializable
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var self[]
     */
    private static $instances = [];

    /**
     * @param string $value
     */
    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @param string $value
     * @return self
     */
    private static function constant(string $value): self
    {
        return self::$instances[$value] ?? self::$instances[$value] = new self($value);
    }

    /**
     * @param string $value
     * @return self
     */
    public static function fromString(string $value): self
    {
        return self::constant($value);
    }

    /**
     * Creates a empty Credential Source
     *
     * @return self
     */
    public static function empty(): self
    {
        return self::fromString('');
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Cloning of constant value objects is not supported
     */
    public function __clone()
    {
        throw new \RuntimeException('The ' . __CLASS__ . ' class is not allowed to be cloned', 1585910952);
    }

    /**
     * Serialization of constant value objects is not supported
     */
    public function __sleep()
    {
        throw new \RuntimeException('The ' . __CLASS__ . ' class is not allowed to be serialized', 1585910954);
    }

    /**
     * Deserialization of constant value objects is not supported
     */
    private function __wakeup()
    {
        throw new \RuntimeException('The ' . __CLASS__ . ' class is not allowed to be de-serialized', 1585910956);
    }
}
