<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication;

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
class ProviderResolverTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveProviderClassThrowsAnExceptionIfNoProviderIsAvailable() {
		$providerResolver = new \F3\FLOW3\Security\Authentication\ProviderResolver($this->objectManager);

		try {
			$providerResolver->resolveProviderClass('IfSomeoneCreatesAClassNamedLikeThisTheFailingOfThisTestIsHisLeastProblem');
			$this->fail('No exception was thrown.');
		} catch (\F3\FLOW3\Security\Exception\NoAuthenticationProviderFound $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveProviderReturnsTheCorrectProviderForAShortName() {
		$providerResolver = new \F3\FLOW3\Security\Authentication\ProviderResolver($this->objectManager);
		$providerClass = $providerResolver->resolveProviderClass('UsernamePassword');

		$this->assertEquals('F3\FLOW3\Security\Authentication\Provider\UsernamePassword', $providerClass, 'The wrong classname has been resolved');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveProviderReturnsTheCorrectProviderForACompleteClassname() {
		$providerResolver = new \F3\FLOW3\Security\Authentication\ProviderResolver($this->objectManager);
		$providerClass = $providerResolver->resolveProviderClass('F3\TestPackage\TestAuthenticationProvider');

		$this->assertEquals('F3\TestPackage\TestAuthenticationProvider', $providerClass, 'The wrong classname has been resolved');
	}
}
?>