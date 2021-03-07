<?php
namespace Neos\Flow\Annotations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Authorization\Privilege\PrivilegeInterface;

/**
 * Used to enable Policy configuration from a method
 *
 * @Annotation
 * @Target("METHOD")
 */
final class Policy
{
    /**
     * Role identifier
     *
     * Example: Vendor.Package:Role
     *
     * @var string
     */
    public $role;

    /**
     * Define permission for the given $role
     *
     * Example: GRANT
     *
     * @var string
     */
    public $permission;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (!isset($values['role'])) {
            throw new \InvalidArgumentException('Role identifier is not provided.', 1614931064);
        }
        if (!isset($values['permission'])) {
            throw new \InvalidArgumentException('No permission is given.', 1614931064);
        }
        if (!in_array($values['permission'], [PrivilegeInterface::ABSTAIN, PrivilegeInterface::DENY, PrivilegeInterface::GRANT])) {
            throw new \InvalidArgumentException(sprintf('Permission value "%s" is invalid. Allowed values are "%s", "%s" and "%s"', $values['permission'], PrivilegeInterface::ABSTAIN, PrivilegeInterface::DENY, PrivilegeInterface::GRANT), 1614931217);
        }

        $this->role = $values['role'];
        $this->permission = $values['permission'];
    }
}
