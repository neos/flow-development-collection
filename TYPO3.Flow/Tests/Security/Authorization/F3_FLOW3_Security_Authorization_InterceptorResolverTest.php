<?php
declare(ENCODING = 'utf-8');

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
 * Testcase for the security interceptor resolver
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_InterceptorResolverTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveInterceptorClassThrowsAnExceptionIfNoInterceptorIsAvailable() {
		$interceptorResolver = new F3_FLOW3_Security_Authorization_InterceptorResolver($this->componentManager);

		try {
			$interceptorResolver->resolveInterceptorClass('IfSomeoneCreatesAClassNamedLikeThisTheFailingOfThisTestIsHisLeastProblem');
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Security_Exception_NoInterceptorFound $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveInterceptorReturnsTheCorrectInterceptorForAShortName() {
		$interceptorResolver = new F3_FLOW3_Security_Authorization_InterceptorResolver($this->componentManager);
		$interceptorClass = $interceptorResolver->resolveInterceptorClass('AccessDeny');

		$this->assertEquals('F3_FLOW3_Security_Authorization_Interceptor_AccessDeny', $interceptorClass, 'The wrong classname has been resolved');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveInterceptorReturnsTheCorrectInterceptorForACompleteClassname() {
		$interceptorResolver = new F3_FLOW3_Security_Authorization_InterceptorResolver($this->componentManager);
		$interceptorClass = $interceptorResolver->resolveInterceptorClass('F3_TestPackage_TestSecurityInterceptor');

		$this->assertEquals('F3_TestPackage_TestSecurityInterceptor', $interceptorClass, 'The wrong classname has been resolved');
	}
}
?>