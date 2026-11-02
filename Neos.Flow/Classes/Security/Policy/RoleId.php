<?php
declare(strict_types=1);

namespace Neos\Flow\Security\Policy;

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
 * A role identifier in the format <Vendor>(.<Package>):<Role>, for example "Some.Package:SomeRole"
 */
final readonly class RoleId
{
    private const ROLE_IDENTIFIER_PATTERN = '/^(\w+(?:\.\w+)*)\:(\w+)$/'; // Vendor(.Package)?:RoleName

    private string $packageKey;
    private string $name;

    private function __construct(
        public string $value,
    ) {
        if (preg_match(self::ROLE_IDENTIFIER_PATTERN, $value, $matches) !== 1) {
            throw new \InvalidArgumentException('The role id must follow the pattern "Vendor.Package:RoleName", but "' . $value . '" was given. Please check the code or policy configuration creating or defining this role.', 1365446549);
        }
        $this->packageKey = $matches[1];
        $this->name = $matches[2];
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function everybody(): self
    {
        return new self('Neos.Flow:Everybody');
    }

    public static function anonymous(): self
    {
        return new self('Neos.Flow:Anonymous');
    }

    public static function authenticatedUser(): self
    {
        return new self('Neos.Flow:AuthenticatedUser');
    }

    /**
     * The package key prefix of the id, e.g. "Some.Package"
     */
    public function getPackageKey(): string
    {
        return $this->packageKey;
    }

    /**
     * The name suffix of the id without its package key prefix, e.g. "SomeRole"
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }
}
