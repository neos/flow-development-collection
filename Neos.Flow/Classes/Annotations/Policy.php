<?php
declare(strict_types=1);

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

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeInterface;

/**
 * Adds a policy configuration to a method
 *
 * This is a convenient way to add policies in project code
 * but should not be used in libraries/packages that shall be
 * configured for different use cases.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD"})
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Policy
{
    public function __construct(
        public readonly string $role,
        public readonly string $permission = 'grant',
    ) {
        if (!in_array($permission, [PrivilegeInterface::ABSTAIN, PrivilegeInterface::DENY, PrivilegeInterface::GRANT])) {
            throw new \InvalidArgumentException(sprintf('Permission value "%s" is invalid. Allowed values are "%s", "%s" and "%s"', $this->permission, PrivilegeInterface::ABSTAIN, PrivilegeInterface::DENY, PrivilegeInterface::GRANT), 1614931217);
        }
    }
}
