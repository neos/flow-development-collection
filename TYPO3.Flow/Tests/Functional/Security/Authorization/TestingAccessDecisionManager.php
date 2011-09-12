<?php
namespace TYPO3\FLOW3\Tests\Functional\Security\Authorization;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An access decision manager that can be overriden for functional tests
 *
 * @scope singleton
 */
class TestingAccessDecisionManager extends \TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterManager {

	/**
	 * @var boolean
	 */
	protected $overrideDecision = NULL;

	/**
	 * Decides on a joinpoint
	 *
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @throws \TYPO3\FLOW3\Security\Exception\AccessDeniedException If access is not granted
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function decideOnJoinPoint(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		if ($this->overrideDecision === FALSE) {
			throw new \TYPO3\FLOW3\Security\Exception\AccessDeniedException('Access denied (override)', 1291652709);
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
	 * @throws \TYPO3\FLOW3\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function decideOnResource($resource) {
		if ($this->overrideDecision === FALSE) {
			throw new \TYPO3\FLOW3\Security\Exception\AccessDeniedException('Access denied (override)', 1291652709);
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
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setOverrideDecision($overrideDecision) {
		$this->overrideDecision = $overrideDecision;
	}

	/**
	 * Resets the AccessDecisionManager to behave transparently.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function reset() {
		$this->overrideDecision = NULL;
	}
}

?>