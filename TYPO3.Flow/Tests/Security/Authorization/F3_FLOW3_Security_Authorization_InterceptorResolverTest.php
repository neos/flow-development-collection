<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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