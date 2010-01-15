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
 * Testcase for the security interceptor resolver
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class InterceptorResolverTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @expectedException F3\FLOW3\Security\Exception\NoInterceptorFoundException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveInterceptorClassThrowsAnExceptionIfNoInterceptorIsAvailable() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$interceptorResolver = new \F3\FLOW3\Security\Authorization\InterceptorResolver($mockObjectManager);

		$interceptorResolver->resolveInterceptorClass('notExistingClass');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveInterceptorReturnsTheCorrectInterceptorForAShortName() {
		$getCaseSensitiveObjectNameCallback = function() {
			$args = func_get_args();

			if ($args[0] === 'F3\FLOW3\Security\Authorization\Interceptor\ValidShortName') return 'F3\FLOW3\Security\Authorization\Interceptor\ValidShortName';

			return FALSE;
		};

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnCallback($getCaseSensitiveObjectNameCallback));


		$interceptorResolver = new \F3\FLOW3\Security\Authorization\InterceptorResolver($mockObjectManager);
		$interceptorClass = $interceptorResolver->resolveInterceptorClass('ValidShortName');

		$this->assertEquals('F3\FLOW3\Security\Authorization\Interceptor\ValidShortName', $interceptorClass, 'The wrong classname has been resolved');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveInterceptorReturnsTheCorrectInterceptorForACompleteClassName() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with('ExistingInterceptorClass')->will($this->returnValue('ExistingInterceptorClass'));

		$interceptorResolver = new \F3\FLOW3\Security\Authorization\InterceptorResolver($mockObjectManager);
		$interceptorClass = $interceptorResolver->resolveInterceptorClass('ExistingInterceptorClass');

		$this->assertEquals('ExistingInterceptorClass', $interceptorClass, 'The wrong classname has been resolved');
	}
}
?>