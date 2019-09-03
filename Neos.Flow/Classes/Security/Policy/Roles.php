<?php
declare(strict_types=1);

namespace Neos\Flow\Security\Policy;

use Neos\Flow\Annotations as Flow;

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
 * A collection class containing all roles for an account
 *
 * @Flow\Proxy(false)
 */
final class Roles implements \JsonSerializable, \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * @var Role[]
     */
    private $roles;

    private function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    public static function create(): self
    {
        return new static([]);
    }

    public static function fromArray(array $roles): self
    {
        $processedRoles = [];
        array_walk($roles, function ($role) use (&$processedRoles) {
            if (!$role instanceof Role) {
                throw new \InvalidArgumentException('Expected instance of Role. Was given ' . gettype($role));
            }
            $processedRoles[(string)$role] = $role;
        });
        return new static($processedRoles);
    }

    public function count(): int
    {
        return count($this->roles);
    }

    /**
     * @param string|Role $role
     * @return bool If the given role is within this collection
     */
    public function has($role): bool
    {
        return array_key_exists((string)$role, $this->roles);
    }

    /**
     * @return \ArrayIterator<Role>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->roles);
    }

    public function jsonSerialize(): array
    {
        return array_values($this->roles);
    }

    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Roles class does not support setting elements via array access.');
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Roles class does not support unsetting elements via array access.');
    }

    public function offsetGet($offset)
    {
        return $this->roles[$offset] ?? null;
    }

    /**
     * @param Role $role The new role to add
     * @return Roles A new instance containing the roles of this instance and the given role
     */
    public function withRole(Role $role)
    {
        return new self(array_merge($this->roles, [(string)$role => $role]));
    }

    /**
     * @param Role $role The role to remove
     * @return Roles A new instance containing the roles of this instance without the given role
     */
    public function withoutRole(Role $role)
    {
        return new self(array_diff($this->roles, [(string)$role => $role]));
    }
}
