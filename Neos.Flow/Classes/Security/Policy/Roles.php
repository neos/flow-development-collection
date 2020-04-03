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
final class Roles implements \JsonSerializable, \IteratorAggregate, \Countable
{

    /**
     * @var Role[]
     */
    private $roles;

    private function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return self
     */
    public static function create(): self
    {
        return new static([]);
    }

    /**
     * @param Role[] $roles
     * @return static
     */
    public static function fromArray(array $roles): self
    {
        $processedRoles = [];
        array_walk($roles, static function ($role) use (&$processedRoles) {
            if (!$role instanceof Role) {
                throw new \InvalidArgumentException(sprintf('Expected instance of Role. Given %s', is_object($role) ? get_class($role) : gettype($role)), 1568888776);
            }
            $processedRoles[(string)$role] = $role;
        });
        return new static($processedRoles);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->roles);
    }

    /**
     * @param Role $role
     * @return bool
     */
    public function has(Role $role): bool
    {
        return array_key_exists((string)$role, $this->roles);
    }

    /**
     * @return \Traversable<Role>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(array_values($this->roles));
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_values($this->roles);
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
     * @param Role $role
     * @return Roles
     */
    public function withRole(Role $role): Roles
    {
        return new self(array_merge($this->roles, [(string)$role => $role]));
    }

    /**
     * @param Role $role
     * @return Roles
     */
    public function withoutRole(Role $role): Roles
    {
        return new self(array_diff($this->roles, [(string)$role => $role]));
    }
}
