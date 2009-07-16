<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization\Interceptor;

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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PolicyEnforcementTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invokeCallsTheAuthenticationManager() {
		$contextHolder = $this->getMock('F3\FLOW3\Security\ContextHolderInterface');
		$context = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$authenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');
		$accessDecisionManager = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$joinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$contextHolder->expects($this->once())->method('getContext')->will($this->returnValue($context));
		$context->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array()));
		$authenticationManager->expects($this->once())->method('authenticate');

		$interceptor = new \F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement($contextHolder, $authenticationManager, $accessDecisionManager);
		$interceptor->setJoinPoint($joinPoint);
		$interceptor->invoke();
	}


	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invokeCallsTheAccessDecisionManagerToDecideOnTheCurrentJoinPoint() {
		$contextHolder = $this->getMock('F3\FLOW3\Security\ContextHolderInterface');
		$context = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$authenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');
		$accessDecisionManager = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$joinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$contextHolder->expects($this->once())->method('getContext')->will($this->returnValue($context));
		$context->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array()));
		$accessDecisionManager->expects($this->once())->method('decide')->with($context, $joinPoint);

		$interceptor = new \F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement($contextHolder, $authenticationManager, $accessDecisionManager);
		$interceptor->setJoinPoint($joinPoint);
		$interceptor->invoke();
	}
}

?>