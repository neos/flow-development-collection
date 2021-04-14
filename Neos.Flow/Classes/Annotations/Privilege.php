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
 * Used to create a Privilege configuration from a method
 *
 * @Annotation
 * @Target("METHOD")
 */
final class Privilege
{
    /**
     * Granted roles
     *
     * Example: ["Neos.Flow:AuthenticatedUser", "Vendor.Package:UserWithSuperRights"]
     *
     * @var array
     */
    public $grantedRoles = [];

    /**
     * Id for the created privilege.
     *
     * If given together with a array of grantedRoles,
     * the id is required to be unique and can not be reused.
     *
     * You can create and reuse a id for several methods,
     * and afterwards configure granted roles from Policy.yaml
     *
     * Example: Vendor.Package:User.UpdateUser
     *
     * @var string
     */
    public $id = null;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['grantedRoles']) && !is_array($values['grantedRoles'])) {
            throw new \InvalidArgumentException('Granted roles must be an array.', 1618389116);
        }
        if (isset($values['grantedRoles'])) {
            $this->grantedRoles = $values['grantedRoles'];
        }

        if (isset($values['id'])) {
            $this->id = $values['id'];
        }
    }
}
