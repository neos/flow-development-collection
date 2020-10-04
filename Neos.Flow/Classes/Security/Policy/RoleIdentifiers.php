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

namespace Neos\Flow\Security\Policy;

use Neos\Flow\Annotations as Flow;

/**
 * A collection class containing all roles for an account
 *
 * @Flow\Proxy(false)
 */
final class RoleIdentifiers implements \JsonSerializable, \IteratorAggregate, \Countable
{

    /**
     * @var string[]
     */
    private $roleIdentifiers;

    private function __construct(array $roleIdentifiers)
    {
        $this->roleIdentifiers = $roleIdentifiers;
    }

    /**
     * @return self
     */
    public static function create(): self
    {
        return new static([]);
    }

    /**
     * @param string[] $roleIdentifiers
     * @return static
     */
    public static function fromArray(array $roleIdentifiers): self
    {
        $processedRoleIdentifiers = [];
        array_walk($roleIdentifiers, static function ($roleIdentifier) use (&$processedRoleIdentifiers) {
            $processedRoleIdentifiers[(string)$roleIdentifier] = $roleIdentifier;
        });
        return new static($processedRoleIdentifiers);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->roleIdentifiers);
    }

    /**
     * @param string $roleIdentifier
     * @return bool
     */
    public function has(string $roleIdentifier): bool
    {
        return array_key_exists((string)$roleIdentifier, $this->roleIdentifiers);
    }

    /**
     * @return \Traversable<Role>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(array_values($this->roleIdentifiers));
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_values($this->roleIdentifiers);
    }

    /**
     * @param $offset
     * @return mixed|Role|null
     */
    public function offsetGet($offset)
    {
        return $this->roles[$offset] ?? null;
    }

    /**
     * @param string $roleIdentifier
     * @return RoleIdentifiers
     */
    public function withRoleIdentifier(string $roleIdentifier): RoleIdentifiers
    {
        return new self(array_merge($this->roleIdentifiers, [(string)$roleIdentifier => $roleIdentifier]));
    }

    /**
     * @param string $roleIdentifier
     * @return RoleIdentifiers
     */
    public function withoutRoleIdentifier(string $roleIdentifier): RoleIdentifiers
    {
        return new self(array_diff($this->roleIdentifiers, [(string)$roleIdentifier => $roleIdentifier]));
    }
}
