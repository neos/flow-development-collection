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

/**
 * An access decision voter manager
 *
 * @Flow\Scope("singleton")
 */
class AccessDecisionVoterManager implements AccessDecisionManagerInterface {

	/**
	 * The object manager
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * The current security context
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * Array of \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface objects
	 * @var array
	 */
	protected $accessDecisionVoters = array();

	/**
	 * If set to TRUE access will be granted for objects where all voters abstain from decision.
	 * @var boolean
	 */
	protected $allowAccessIfAllAbstain = FALSE;

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager The object manager
	 * @param \TYPO3\Flow\Security\Context $securityContext The security context
	 */
	public function __construct(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager, \TYPO3\Flow\Security\Context $securityContext) {
		$this->objectManager = $objectManager;
		$this->securityContext = $securityContext;
	}

	/**
	 * Injects the configuration settings
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->createAccessDecisionVoters($settings['security']['authorization']['accessDecisionVoters']);
		$this->allowAccessIfAllAbstain = $settings['security']['authorization']['allowAccessIfAllVotersAbstain'];
	}

	/**
	 * Returns the configured access decision voters
	 *
	 * @return array Array of \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface objects
	 */
	public function getAccessDecisionVoters() {
		return $this->accessDecisionVoters;
	}

	/**
	 * Decides if access should be granted on the given object in the current security context.
	 * It iterates over all available \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface objects.
	 * If all voters abstain, access will be denied by default, except $allowAccessIfAllAbstain is set to TRUE.
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function decideOnJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$denyVotes = 0;
		$grantVotes = 0;
		$abstainVotes = 0;

		/** @var $voter \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface */
		foreach ($this->accessDecisionVoters as $voter) {
			$vote = $voter->voteForJoinPoint($this->securityContext, $joinPoint);
			switch ($vote) {
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY:
					$denyVotes++;
					break;
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT:
					$grantVotes++;
					break;
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN:
					$abstainVotes++;
					break;
			}
		}

		if ($denyVotes === 0 && $grantVotes > 0) {
			return;
		}
		if ($denyVotes === 0 && $grantVotes === 0 && $abstainVotes > 0 && $this->allowAccessIfAllAbstain === TRUE) {
			return;
		}

		$votes = sprintf('(%d denied, %d granted, %d abstained)', $denyVotes, $grantVotes, $abstainVotes);
		throw new \TYPO3\Flow\Security\Exception\AccessDeniedException('Access denied ' . $votes, 1222268609);
	}

	/**
	 * Decides if access should be granted on the given resource in the current security context.
	 * It iterates over all available \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface objects.
	 * If all voters abstain, access will be denied by default, except $allowAccessIfAllAbstain is set to TRUE.
	 *
	 * @param string $resource The resource to decide on
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function decideOnResource($resource) {
		$denyVotes = 0;
		$grantVotes = 0;
		$abstainVotes = 0;

		/** @var $voter \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface */
		foreach ($this->accessDecisionVoters as $voter) {
			$vote = $voter->voteForResource($this->securityContext, $resource);
			switch ($vote) {
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_DENY:
					$denyVotes++;
					break;
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_GRANT:
					$grantVotes++;
					break;
				case \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface::VOTE_ABSTAIN:
					$abstainVotes++;
					break;
			}
		}

		if ($denyVotes === 0 && $grantVotes > 0) {
			return;
		}
		if ($denyVotes === 0 && $grantVotes === 0 && $abstainVotes > 0 && $this->allowAccessIfAllAbstain === TRUE) {
			return;
		}

		$votes = sprintf('(%d denied, %d granted, %d abstained)', $denyVotes, $grantVotes, $abstainVotes);
		throw new \TYPO3\Flow\Security\Exception\AccessDeniedException('Access denied ' . $votes, 1283175927);
	}

	/**
	 * Returns TRUE if access is granted on the given resource in the current security context
	 *
	 * @param string $resource The resource to decide on
	 * @return boolean TRUE if access is granted, FALSE otherwise
	 */
	public function hasAccessToResource($resource) {
		try {
			$this->decideOnResource($resource);
		} catch(\TYPO3\Flow\Security\Exception\AccessDeniedException $exception) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Creates and sets the configured access decision voters
	 *
	 * @param array $voterClassNames Array of access decision voter class names
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\VoterNotFoundException
	 */
	protected function createAccessDecisionVoters(array $voterClassNames) {
		foreach ($voterClassNames as $voterClassName) {
			if (!$this->objectManager->isRegistered($voterClassName)) {
				throw new \TYPO3\Flow\Security\Exception\VoterNotFoundException('No voter of type ' . $voterClassName . ' found!', 1222267934);
			}

			$voter = $this->objectManager->get($voterClassName);
			if (!($voter instanceof \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface)) {
				throw new \TYPO3\Flow\Security\Exception\VoterNotFoundException('The found voter class did not implement \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface', 1222268008);
			}

			$this->accessDecisionVoters[] = $voter;
		}
	}
}

?>