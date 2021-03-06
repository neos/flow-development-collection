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
     * If set to true access will be granted for objects where all voters abstain from decision.
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
     * Returns true, if the given privilege type is granted for the given subject based
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
     * Returns true, if the given privilege type would be granted for the given roles and subject
     *
     * @param array<Role> $roles The roles that should be evaluated
     * @param string $privilegeType The type of privilege that should be evaluated
     * @param mixed $subject The subject to check privileges for
     * @param string $reason This variable will be filled by a message giving information about the reasons for the result of this method
     * @return boolean
     */
    public function isGrantedForRoles(array $roles, $privilegeType, $subject, &$reason = '')
    {
        $availablePrivileges = array_reduce($roles, $this->getPrivilegeByTypeReducer($privilegeType), []);
        $effectivePrivileges = array_filter($availablePrivileges, $this->getPrivilegeSubjectFilter($subject));
        /** @var PrivilegePermissionResult $result */
        $result = array_reduce($effectivePrivileges, [$this, 'applyPrivilegeToResult'], new PrivilegePermissionResult());

        $effectivePrivilegeIdentifiersWithPermission = $result->getEffectivePrivilegeIdentifiersWithPermission();
        if ($effectivePrivilegeIdentifiersWithPermission === []) {
            $reason = sprintf('No privilege of type "%s" matched.', $privilegeType);
            return true;
        }

        $reason = sprintf(
            'Evaluated following %d privilege target(s):' . chr(10) . '%s' . chr(10) . '(%d granted, %d denied, %d abstained)',
            count($effectivePrivilegeIdentifiersWithPermission),
            implode(chr(10), $effectivePrivilegeIdentifiersWithPermission),
            $result->getGrants(),
            $result->getDenies(),
            $result->getAbstains()
        );

        if ($result->getDenies() > 0) {
            return false;
        }
        if ($result->getGrants() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if access is granted on the given privilege target in the current security context
     *
     * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
     * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
     * @return boolean true if access is granted, false otherwise
     */
    public function isPrivilegeTargetGranted($privilegeTargetIdentifier, array $privilegeParameters = [])
    {
        return $this->isPrivilegeTargetGrantedForRoles($this->securityContext->getRoles(), $privilegeTargetIdentifier, $privilegeParameters);
    }

    /**
     * Returns true if access is granted on the given privilege target in the current security context
     *
     * @param array<Role> $roles The roles that should be evaluated
     * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
     * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
     * @return boolean true if access is granted, false otherwise
     */
    public function isPrivilegeTargetGrantedForRoles(array $roles, $privilegeTargetIdentifier, array $privilegeParameters = [])
    {
        $privilegeMapper = function (Role $role) use ($privilegeTargetIdentifier, $privilegeParameters) {
            return $role->getPrivilegeForTarget($privilegeTargetIdentifier, $privilegeParameters);
        };

        $privileges = array_map($privilegeMapper, $roles);
        /** @var PrivilegePermissionResult $result */
        $result = array_reduce($privileges, [$this, 'applyPrivilegeToResult'], new PrivilegePermissionResult());

        if ($result->getDenies() === 0 && $result->getGrants() > 0) {
            return true;
        }

        $privilegeFound = $privileges !== [];
        if ($result->getDenies() === 0 && $result->getGrants() === 0 && $privilegeFound === true && $this->allowAccessIfAllAbstain === true) {
            return true;
        }

        return false;
    }

    /**
     * @param PrivilegePermissionResult $result
     * @param PrivilegeInterface|null $privilege
     * @return PrivilegePermissionResult
     */
    protected function applyPrivilegeToResult(PrivilegePermissionResult $result, PrivilegeInterface $privilege = null): PrivilegePermissionResult
    {
        return $result->withPrivilege($privilege);
    }

    /**
     * @param string $privilegeType
     * @return \Closure
     */
    protected function getPrivilegeByTypeReducer(string $privilegeType): \Closure
    {
        return function (array $availablePrivileges, Role $role) use ($privilegeType) {
            return array_merge($availablePrivileges, $role->getPrivilegesByType($privilegeType));
        };
    }

    /**
     * @param mixed $subject
     * @return \Closure
     */
    protected function getPrivilegeSubjectFilter($subject): \Closure
    {
        return function (PrivilegeInterface $privilege) use ($subject) {
            return $privilege->matchesSubject($subject);
        };
    }
}
