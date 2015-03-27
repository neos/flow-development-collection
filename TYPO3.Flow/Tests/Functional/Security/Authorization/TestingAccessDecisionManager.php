<?php
namespace TYPO3\Flow\Tests\Functional\Security\Authorization;

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
 * An access decision manager that can be overriden for functional tests
 *
 * @Flow\Scope("singleton")
 */
class TestingAccessDecisionManager extends \TYPO3\Flow\Security\Authorization\AccessDecisionVoterManager {

	/**
	 * @var boolean
	 */
	protected $overrideDecision = NULL;

	/**
	 * Decides on a joinpoint
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function decideOnJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		if ($this->overrideDecision === FALSE) {
			throw new \TYPO3\Flow\Security\Exception\AccessDeniedException('Access denied (override)', 1291652709);
		} elseif ($this->overrideDecision === TRUE) {
			return;
		}
		parent::decideOnJoinPoint($joinPoint);
	}

	/**
	 * Decides on a resource.
	 *
	 * @param string $resource The resource to decide on
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function decideOnResource($resource) {
		if ($this->overrideDecision === FALSE) {
			throw new \TYPO3\Flow\Security\Exception\AccessDeniedException('Access denied (override)', 1291652709);
		} elseif ($this->overrideDecision === TRUE) {
			return;
		}
		parent::decideOnResource($resource);
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
