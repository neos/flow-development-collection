<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization\Interceptor;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 */

/**
 * Testcase for the policy enforcement interceptor
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PolicyEnforcementTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invokeCallsTheAuthenticationManagerIfAuthenticationHasNotBeenPerformed() {
		$contextHolder = $this->getMock('F3\FLOW3\Security\ContextHolderInterface');
		$context = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$authenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');
		$accessDecisionManager = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$joinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$contextHolder->expects($this->once())->method('getContext')->will($this->returnValue($context));
		$context->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array()));
		$context->expects($this->atLeastOnce())->method('authenticationPerformed')->will($this->returnValue(FALSE));
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
	public function invokeDoesNotCallTheAuthenticationManagerIfAuthenticationAlreadyHasBeenPerformed() {
		$contextHolder = $this->getMock('F3\FLOW3\Security\ContextHolderInterface');
		$context = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$authenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');
		$accessDecisionManager = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$joinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$contextHolder->expects($this->once())->method('getContext')->will($this->returnValue($context));
		$context->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array()));
		$context->expects($this->atLeastOnce())->method('authenticationPerformed')->will($this->returnValue(TRUE));
		$authenticationManager->expects($this->never())->method('authenticate');

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

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Security\Exception\AuthenticationRequired
	 */
	public function invokeCallsTheFirstAvailableAuthenticationEntryPointIfAuthenticationFailed() {
		$contextHolder = $this->getMock('F3\FLOW3\Security\ContextHolderInterface');
		$context = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$authenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');
		$accessDecisionManager = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$joinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$authenticationEntryPoint = $this->getMock('F3\FLOW3\Security\Authentication\EntryPointInterface');
		$authenticationEntryPoint->expects($this->once())->method('startAuthentication');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$token1->expects($this->atLeastOnce())->method('getAuthenticationEntryPoint')->will($this->returnValue(NULL));
		$token2->expects($this->atLeastOnce())->method('getAuthenticationEntryPoint')->will($this->returnValue($authenticationEntryPoint));

		$contextHolder->expects($this->once())->method('getContext')->will($this->returnValue($context));
		$context->expects($this->atLeastOnce())->method('authenticationPerformed')->will($this->returnValue(FALSE));
		$context->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));
		$authenticationManager->expects($this->once())->method('authenticate')->will($this->throwException(new \F3\FLOW3\Security\Exception\AuthenticationRequired()));

		$interceptor = new \F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement($contextHolder, $authenticationManager, $accessDecisionManager);
		$interceptor->setJoinPoint($joinPoint);

			// Usually the request is aborted/redirected by calling an entrypoint
		$interceptor->invoke();
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invokeThrowsAnExceptionIfAuthenticationFailedAndNoAuthenticationEntryPointIsAvailable() {
		$contextHolder = $this->getMock('F3\FLOW3\Security\ContextHolderInterface');
		$context = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$authenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');
		$accessDecisionManager = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$joinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$token1->expects($this->once())->method('getAuthenticationEntryPoint')->will($this->returnValue(NULL));
		$token2->expects($this->once())->method('getAuthenticationEntryPoint')->will($this->returnValue(NULL));

		$contextHolder->expects($this->once())->method('getContext')->will($this->returnValue($context));
		$context->expects($this->atLeastOnce())->method('authenticationPerformed')->will($this->returnValue(FALSE));
		$context->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));
		$authenticationManager->expects($this->once())->method('authenticate')->will($this->throwException(new \F3\FLOW3\Security\Exception\AuthenticationRequired()));

		$interceptor = new \F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement($contextHolder, $authenticationManager, $accessDecisionManager);
		$interceptor->setJoinPoint($joinPoint);

		try {
			$interceptor->invoke();
			$this->fail('No exception has been thrown.');
		} catch (\F3\FLOW3\Security\Exception\AuthenticationRequired $exception) {}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invokeDoesNotCallAnEntryPointIfWeAreInTheEntryPoint() {
	$contextHolder = $this->getMock('F3\FLOW3\Security\ContextHolderInterface');
		$context = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$authenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');
		$accessDecisionManager = $this->getMock('F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$joinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$authenticationEntryPoint = $this->getMock('F3\FLOW3\Security\Authentication\EntryPointInterface');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$token1->expects($this->atLeastOnce())->method('getAuthenticationEntryPoint')->will($this->returnValue(NULL));
		$token2->expects($this->atLeastOnce())->method('getAuthenticationEntryPoint')->will($this->returnValue($authenticationEntryPoint));
		$authenticationEntryPoint->expects($this->never())->method('startAuthentication');

		$joinPoint->expects($this->atLeastOnce())->method('getProxy')->will($this->returnValue($authenticationEntryPoint));
		$contextHolder->expects($this->once())->method('getContext')->will($this->returnValue($context));
		$context->expects($this->atLeastOnce())->method('authenticationPerformed')->will($this->returnValue(FALSE));
		$context->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));
		$authenticationManager->expects($this->once())->method('authenticate')->will($this->throwException(new \F3\FLOW3\Security\Exception\AuthenticationRequired()));

		$interceptor = new \F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement($contextHolder, $authenticationManager, $accessDecisionManager);
		$interceptor->setJoinPoint($joinPoint);

		//Usually the request is aborted/redirected by calling an entrypoint
		try {
			$interceptor->invoke();
			$this->fail('No exception has been thrown.');
		} catch (\F3\FLOW3\Security\Exception\AuthenticationRequired $exception) {}
	}
}

?>