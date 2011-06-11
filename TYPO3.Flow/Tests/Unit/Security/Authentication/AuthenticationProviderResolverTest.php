<?php
namespace F3\FLOW3\Tests\Unit\Security\Authentication;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AuthenticationProviderResolverTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException F3\FLOW3\Security\Exception\NoAuthenticationProviderFoundException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveProviderObjectNameThrowsAnExceptionIfNoProviderIsAvailable() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$providerResolver = new \F3\FLOW3\Security\Authentication\AuthenticationProviderResolver($mockObjectManager);

		$providerResolver->resolveProviderClass('notExistingClass');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveProviderReturnsTheCorrectProviderForAShortName() {
		$getCaseSensitiveObjectNameCallback = function() {
			$args = func_get_args();

			if ($args[0] === 'F3\FLOW3\Security\Authentication\Provider\ValidShortName') return 'F3\FLOW3\Security\Authentication\Provider\ValidShortName';

			return FALSE;
		};

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnCallback($getCaseSensitiveObjectNameCallback));

		$providerResolver = new \F3\FLOW3\Security\Authentication\AuthenticationProviderResolver($mockObjectManager);
		$providerClass = $providerResolver->resolveProviderClass('ValidShortName');

		$this->assertEquals('F3\FLOW3\Security\Authentication\Provider\ValidShortName', $providerClass, 'The wrong classname has been resolved');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveProviderReturnsTheCorrectProviderForACompleteClassName() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with('existingProviderClass')->will($this->returnValue('existingProviderClass'));

		$providerResolver = new \F3\FLOW3\Security\Authentication\AuthenticationProviderResolver($mockObjectManager);
		$providerClass = $providerResolver->resolveProviderClass('existingProviderClass');

		$this->assertEquals('existingProviderClass', $providerClass, 'The wrong classname has been resolved');
	}
}
?>