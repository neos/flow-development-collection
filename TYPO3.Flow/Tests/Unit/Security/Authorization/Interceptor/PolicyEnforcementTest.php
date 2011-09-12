<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authorization\Interceptor;

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
 * Testcase for the policy enforcement interceptor
 *
 */
class PolicyEnforcementTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invokeCallsTheAuthenticationManager() {
		$authenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$accessDecisionManager = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$joinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface');

		$authenticationManager->expects($this->once())->method('authenticate');

		$interceptor = new \TYPO3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement($authenticationManager, $accessDecisionManager);
		$interceptor->setJoinPoint($joinPoint);
		$interceptor->invoke();
	}


	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invokeCallsTheAccessDecisionManagerToDecideOnTheCurrentJoinPoint() {
		$authenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$accessDecisionManager = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$joinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface');

		$accessDecisionManager->expects($this->once())->method('decideOnJoinPoint')->with($joinPoint);

		$interceptor = new \TYPO3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement($authenticationManager, $accessDecisionManager);
		$interceptor->setJoinPoint($joinPoint);
		$interceptor->invoke();
	}
}

?>