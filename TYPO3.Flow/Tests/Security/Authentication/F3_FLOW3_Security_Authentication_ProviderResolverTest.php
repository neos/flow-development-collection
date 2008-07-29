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
class F3_FLOW3_Security_Authentication_ProviderResolverTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveProviderClassThrowsAnExceptionIfNoProviderIsAvailable() {
		$providerResolver = new F3_FLOW3_Security_Authentication_ProviderResolver($this->componentManager);

		try {
			$providerResolver->resolveProviderClass('IfSomeoneCreatesAClassNamedLikeThisTheFailingOfThisTestIsHisLeastProblem');
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Security_Exception_NoAuthenticationProviderFound $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveProviderReturnsTheCorrectProviderForAShortName() {
		$providerResolver = new F3_FLOW3_Security_Authentication_ProviderResolver($this->componentManager);
		$providerClass = $providerResolver->resolveProviderClass('UsernamePassword');

		$this->assertEquals('F3_FLOW3_Security_Authentication_Provider_UsernamePassword', $providerClass, 'The wrong classname has been resolved');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveProviderReturnsTheCorrectProviderForACompleteClassname() {
		$providerResolver = new F3_FLOW3_Security_Authentication_ProviderResolver($this->componentManager);
		$providerClass = $providerResolver->resolveProviderClass('F3_TestPackage_TestAuthenticationProvider');

		$this->assertEquals('F3_TestPackage_TestAuthenticationProvider', $providerClass, 'The wrong classname has been resolved');
	}
}
?>