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
 * An access decision manager that can be overridden for tests
 *
 * @Flow\Scope("singleton")
 */
class TestingPrivilegeManager extends PrivilegeManager {

	/**
	 * @var boolean
	 */
	protected $overrideDecision = NULL;

	/**
	 * Returns TRUE, if the given privilege type is granted for the given subject based
	 * on the current security context or if set based on the override decision value.
	 *
	 * @param string $privilegeType
	 * @param mixed $subject
	 * @param array $voteResults This variable will be filled by PrivilegeVoteResult objects, giving information about the reasons for the result of this method
	 * @return boolean
	 */
	public function isGranted($privilegeType, $subject, &$voteResults = array()) {
		if ($this->overrideDecision === FALSE) {
			$voteResults[] = new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_DENY, 'Voting has been overriden to "DENY" by the testing privilege manager!');
			return FALSE;
		} elseif ($this->overrideDecision === TRUE) {
			$voteResults[] = new PrivilegeVoteResult(PrivilegeVoteResult::VOTE_GRANT, 'Voting has been overriden to "GRANT" by the testing privilege manager!');
			return TRUE;
		}
		return parent::isGranted($privilegeType, $subject, $voteResults);
	}

	/**
	 * Returns TRUE if access is granted on the given privilege target in the current security context
	 * or if set based on the override decision value.
	 *
	 * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
	 * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
	 * @return boolean TRUE if access is granted, FALSE otherwise
	 */
	public function isPrivilegeTargetGranted($privilegeTargetIdentifier, array $privilegeParameters = array()) {
		if ($this->overrideDecision === FALSE) {
			return FALSE;
		} elseif ($this->overrideDecision === TRUE) {
			return TRUE;
		}
		return parent::isPrivilegeTargetGranted($privilegeTargetIdentifier, $privilegeParameters);
	}

	/**
	 * Set the decision override
	 *
	 * @param boolean $overrideDecision TRUE or FALSE to override the decision, NULL to use the access decision voter manager
	 * @return void
	 */
	public function setOverrideDecision($overrideDecision) {
		$this->overrideDecision = $overrideDecision;
	}

	/**
	 * Resets the AccessDecisionManager to behave transparently.
	 *
	 * @return void
	 */
	public function reset() {
		$this->overrideDecision = NULL;
	}
}
