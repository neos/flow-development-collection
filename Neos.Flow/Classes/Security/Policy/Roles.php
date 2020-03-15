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

    public static function create(): self
    {
        return new static([]);
    }


    public static function fromArray(array $roles): self
    {
        $processedRoles = [];
        array_walk($roles, function ($role) use (&$processedRoles) {
            if (!$role instanceof Role) {
                throw new \InvalidArgumentException(
                    sprintf('Expected instance of Role. Given %s', is_object($role) ? get_class($role) : gettype($role)),
                    1568888776
                );
            }
            $processedRoles[(string)$role] = $role;
        });
        return new static($processedRoles);
    }

    public function count(): int
    {
        return count($this->roles);
    }

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

    public function jsonSerialize(): array
    {
        return array_values($this->roles);
    }

    public function offsetGet($offset)
    {
        return $this->roles[$offset] ?? null;
    }

    public function withRole(Role $role): Roles
    {
        return new self(array_merge($this->roles, [(string)$role => $role]));
    }

    public function withoutRole(Role $role): Roles
    {
        return new self(array_diff($this->roles, [(string)$role => $role]));
    }
}
