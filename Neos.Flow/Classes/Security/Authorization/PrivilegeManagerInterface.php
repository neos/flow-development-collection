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

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * Contract for a privilege manager
 */
interface PrivilegeManagerInterface
{
    /**
     * Returns TRUE, if the given privilege type is granted for the given subject based
     * on the current security context.
     *
     * @param string $privilegeType The type of privilege that should be evaluated
     * @param mixed $subject The subject to check privileges for
     * @param string $reason This variable will be filled by a message giving information about the reasons for the result of this method
     * @return boolean
     */
    public function isGranted($privilegeType, $subject, &$reason = '');

    /**
     * Returns TRUE, if the given privilege type would be granted for the given roles and subject
     *
     * @param array<Role> $roles The roles that should be evaluated
     * @param string $privilegeType The type of privilege that should be evaluated
     * @param mixed $subject The subject to check privileges for
     * @param string $reason This variable will be filled by a message giving information about the reasons for the result of this method
     * @return boolean
     */
    public function isGrantedForRoles(array $roles, $privilegeType, $subject, &$reason = '');

    /**
     * Returns TRUE if access is granted on the given privilege target in the current security context
     *
     * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
     * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
     * @return boolean TRUE if access is granted, FALSE otherwise
     */
    public function isPrivilegeTargetGranted($privilegeTargetIdentifier, array $privilegeParameters = []);

    /**
     * Returns TRUE if access is granted on the given privilege target in the current security context
     *
     * @param array<Role> $roles The roles that should be evaluated
     * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
     * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
     * @return boolean TRUE if access is granted, FALSE otherwise
     */
    public function isPrivilegeTargetGrantedForRoles(array $roles, $privilegeTargetIdentifier, array $privilegeParameters = []);
}
