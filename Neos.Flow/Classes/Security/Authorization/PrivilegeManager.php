<?php
namespace Neos\Flow\Security\Authorization;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\Role;

/**
 * An access decision voter manager
 *
 * @Flow\Scope("singleton")
 */
class PrivilegeManager implements PrivilegeManagerInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Context
     */
    protected $securityContext;

    /**
     * If set to TRUE access will be granted for objects where all voters abstain from decision.
     *
     * @Flow\InjectConfiguration("security.authorization.allowAccessIfAllVotersAbstain")
     * @var boolean
     */
    protected $allowAccessIfAllAbstain = false;

    /**
     * @param ObjectManagerInterface $objectManager The object manager
     * @param Context $securityContext The current security context
     */
    public function __construct(ObjectManagerInterface $objectManager, Context $securityContext)
    {
        $this->objectManager = $objectManager;
        $this->securityContext = $securityContext;
    }

    /**
     * Returns TRUE, if the given privilege type is granted for the given subject based
     * on the current security context.
     *
     * @param string $privilegeType The type of privilege that should be evaluated
     * @param mixed $subject The subject to check privileges for
     * @param string $reason This variable will be filled by a message giving information about the reasons for the result of this method
     * @return boolean
     */
    public function isGranted($privilegeType, $subject, &$reason = '')
    {
        return $this->isGrantedForRoles($this->securityContext->getRoles(), $privilegeType, $subject, $reason);
    }

    /**
     * Returns TRUE, if the given privilege type would be granted for the given roles and subject
     *
     * @param array<Role> $roles The roles that should be evaluated
     * @param string $privilegeType The type of privilege that should be evaluated
     * @param mixed $subject The subject to check privileges for
     * @param string $reason This variable will be filled by a message giving information about the reasons for the result of this method
     * @return boolean
     */
    public function isGrantedForRoles(array $roles, $privilegeType, $subject, &$reason = '')
    {
        $effectivePrivilegeIdentifiersWithPermission = [];
        $accessGrants = 0;
        $accessDenies = 0;
        $accessAbstains = 0;
        /** @var Role $role */
        foreach ($roles as $role) {
            /** @var PrivilegeInterface[] $availablePrivileges */
            $availablePrivileges = $role->getPrivilegesByType($privilegeType);
            /** @var PrivilegeInterface[] $effectivePrivileges */
            $effectivePrivileges = [];
            foreach ($availablePrivileges as $privilege) {
                if ($privilege->matchesSubject($subject)) {
                    $effectivePrivileges[] = $privilege;
                }
            }

            foreach ($effectivePrivileges as $effectivePrivilege) {
                $privilegeName = $effectivePrivilege->getPrivilegeTargetIdentifier();
                $parameterStrings = [];
                foreach ($effectivePrivilege->getParameters() as $parameter) {
                    $parameterStrings[] = sprintf('%s: "%s"', $parameter->getName(), $parameter->getValue());
                }
                if ($parameterStrings !== []) {
                    $privilegeName .= ' (with parameters: ' . implode(', ', $parameterStrings) . ')';
                }

                $effectivePrivilegeIdentifiersWithPermission[] = sprintf('"%s": %s', $privilegeName, strtoupper($effectivePrivilege->getPermission()));
                if ($effectivePrivilege->isGranted()) {
                    $accessGrants++;
                } elseif ($effectivePrivilege->isDenied()) {
                    $accessDenies++;
                } else {
                    $accessAbstains++;
                }
            }
        }

        if (count($effectivePrivilegeIdentifiersWithPermission) === 0) {
            $reason = sprintf('No privilege of type "%s" matched.', $privilegeType);
            return true;
        } else {
            $reason = sprintf('Evaluated following %d privilege target(s):' . chr(10) . '%s' . chr(10) . '(%d granted, %d denied, %d abstained)', count($effectivePrivilegeIdentifiersWithPermission), implode(chr(10), $effectivePrivilegeIdentifiersWithPermission), $accessGrants, $accessDenies, $accessAbstains);
        }
        if ($accessDenies > 0) {
            return false;
        }
        if ($accessGrants > 0) {
            return true;
        }

        return false;
    }

    /**
     * Returns TRUE if access is granted on the given privilege target in the current security context
     *
     * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
     * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
     * @return boolean TRUE if access is granted, FALSE otherwise
     */
    public function isPrivilegeTargetGranted($privilegeTargetIdentifier, array $privilegeParameters = [])
    {
        return $this->isPrivilegeTargetGrantedForRoles($this->securityContext->getRoles(), $privilegeTargetIdentifier, $privilegeParameters);
    }

    /**
     * Returns TRUE if access is granted on the given privilege target in the current security context
     *
     * @param array<Role> $roles The roles that should be evaluated
     * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
     * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
     * @return boolean TRUE if access is granted, FALSE otherwise
     */
    public function isPrivilegeTargetGrantedForRoles(array $roles, $privilegeTargetIdentifier, array $privilegeParameters = [])
    {
        $privilegeFound = false;
        $accessGrants = 0;
        $accessDenies = 0;
        /** @var Role $role */
        foreach ($roles as $role) {
            $privilege = $role->getPrivilegeForTarget($privilegeTargetIdentifier, $privilegeParameters);
            if ($privilege === null) {
                continue;
            }

            $privilegeFound = true;

            if ($privilege->isGranted()) {
                $accessGrants++;
            } elseif ($privilege->isDenied()) {
                $accessDenies++;
            }
        }

        if ($accessDenies === 0 && $accessGrants > 0) {
            return true;
        }

        if ($accessDenies === 0 && $accessGrants === 0 && $privilegeFound === true && $this->allowAccessIfAllAbstain === true) {
            return true;
        }

        return false;
    }
}
