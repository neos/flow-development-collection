<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization;

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
 * @version $Id$
 */

/**
 * Testcase for the security interceptor resolver
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class InterceptorResolverTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveInterceptorClassThrowsAnExceptionIfNoInterceptorIsAvailable() {
		$interceptorResolver = new \F3\FLOW3\Security\Authorization\InterceptorResolver($this->objectManager);

		try {
			$interceptorResolver->resolveInterceptorClass('IfSomeoneCreatesAClassNamedLikeThisTheFailingOfThisTestIsHisLeastProblem');
			$this->fail('No exception was thrown.');
		} catch (\F3\FLOW3\Security\Exception\NoInterceptorFound $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveInterceptorReturnsTheCorrectInterceptorForAShortName() {
		$interceptorResolver = new \F3\FLOW3\Security\Authorization\InterceptorResolver($this->objectManager);
		$interceptorClass = $interceptorResolver->resolveInterceptorClass('AccessDeny');

		$this->assertEquals('F3\FLOW3\Security\Authorization\Interceptor\AccessDeny', $interceptorClass, 'The wrong classname has been resolved');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveInterceptorReturnsTheCorrectInterceptorForACompleteClassname() {
		$interceptorResolver = new \F3\FLOW3\Security\Authorization\InterceptorResolver($this->objectManager);
		$interceptorClass = $interceptorResolver->resolveInterceptorClass('F3\TestPackage\TestSecurityInterceptor');

		$this->assertEquals('F3\TestPackage\TestSecurityInterceptor', $interceptorClass, 'The wrong classname has been resolved');
	}
}
?>