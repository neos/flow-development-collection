<?php
namespace TYPO3\Flow\Security\Authorization;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Contract for a privilege manager
 */
interface PrivilegeManagerInterface {

	/**
	 * Returns TRUE, if the given privilege type is granted for the given subject based
	 * on the current security context.
	 *
	 * @param string $privilegeType The type of privilege that should be evaluated
	 * @param mixed $subject The subject to check privileges for
	 * @param array $voteResults This variable will be filled by PrivilegeVoteResult objects, giving information about the reasons for the result of this method
	 * @return boolean
	 */
	public function isGranted($privilegeType, $subject, &$voteResults = array());

	/**
	 * Returns TRUE if access is granted on the given privilege target in the current security context
	 *
	 * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
	 * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
	 * @return boolean TRUE if access is granted, FALSE otherwise
	 */
	public function isPrivilegeTargetGranted($privilegeTargetIdentifier, array $privilegeParameters = array());

}
