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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Security\Context;

/**
 * An access decision voter manager
 *
 * @Flow\Scope("singleton")
 */
class PrivilegeManager implements PrivilegeManagerInterface {

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * If set to TRUE access will be granted for objects where all voters abstain from decision.
	 *
	 * @var boolean
	 */
	protected $allowAccessIfAllAbstain = FALSE;

	/**
	 * @param ObjectManagerInterface $objectManager The object manager
	 * @param ReflectionService $reflectionService
	 * @param Context $securityContext The current security context
	 */
	public function __construct(ObjectManagerInterface $objectManager, ReflectionService $reflectionService, Context $securityContext) {
		$this->objectManager = $objectManager;
		$this->reflectionService = $reflectionService;
		$this->securityContext = $securityContext;
	}

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->allowAccessIfAllAbstain = $settings['security']['authorization']['allowAccessIfAllVotersAbstain'];
	}

	/**
	 * Returns TRUE, if the given privilege type is granted for the given subject based
	 * on the current security context.
	 *
	 * @param string $privilegeType The type of privilege that should be evaluated
	 * @param mixed $subject The subject to check privileges for
	 * @param array $voteResults This variable will be filled by PrivilegeVoteResult objects, giving information about the reasons for the result of this method
	 * @return boolean
	 */
	public function isGranted($privilegeType, $subject, &$voteResults = array()) {
		$votes = array(
			PrivilegeVoteResult::VOTE_DENY => 0,
			PrivilegeVoteResult::VOTE_GRANT => 0,
			PrivilegeVoteResult::VOTE_ABSTAIN => 0
		);
		$voteResults = array();

		if (interface_exists($privilegeType) === TRUE) {
			$privilegeClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface($privilegeType);
		} else {
			$privilegeClassNames = $this->reflectionService->getAllSubClassNamesForClass($privilegeType);
			$privilegeClassNames[] = $privilegeType;
		}

		foreach ($privilegeClassNames as $privilegeClassName) {
			if ($this->reflectionService->isClassAbstract($privilegeClassName) === FALSE) {
				$currentVoteResult = call_user_func_array(array($privilegeClassName, 'vote'), array($subject));
				$voteResults[] = $currentVoteResult;

				if (isset($votes[$currentVoteResult->getVote()])) {
					$votes[$currentVoteResult->getVote()]++;
				}
			}
		}

		if ($votes[PrivilegeVoteResult::VOTE_DENY] === 0 && $votes[PrivilegeVoteResult::VOTE_GRANT] > 0) {
			return TRUE;
		}
		if ($votes[PrivilegeVoteResult::VOTE_DENY] === 0
			&& $votes[PrivilegeVoteResult::VOTE_GRANT] === 0
			&& $votes[PrivilegeVoteResult::VOTE_ABSTAIN] > 0 && $this->allowAccessIfAllAbstain === TRUE) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns TRUE if access is granted on the given privilege target in the current security context
	 *
	 * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
	 * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
	 * @return boolean TRUE if access is granted, FALSE otherwise
	 */
	public function isPrivilegeTargetGranted($privilegeTargetIdentifier, array $privilegeParameters = array()) {
		$privilegeFound = FALSE;
		$accessGrants = 0;
		$accessDenies = 0;
		foreach ($this->securityContext->getRoles() as $role) {
			$privilege = $role->getPrivilegeForTarget($privilegeTargetIdentifier, $privilegeParameters);
			if ($privilege === NULL) {
				continue;
			}

			$privilegeFound = TRUE;

			if ($privilege->isGranted()) {
				$accessGrants++;
			} elseif ($privilege->isDenied()) {
				$accessDenies++;
			}
		}

		if ($accessDenies === 0 && $accessGrants > 0) {
			return TRUE;
		}

		if ($accessDenies === 0 && $accessGrants === 0 && $privilegeFound === TRUE && $this->allowAccessIfAllAbstain === TRUE) {
			return TRUE;
		}

		return FALSE;
	}
}
