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
 * Testcase for authentication provider manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authentication_ProviderManagerTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredProvidersAndTokensAreBuiltCorrectly() {
		$mockConfigurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array(), array(), '', FALSE);
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->security->authentication->providers = array(
			array(
				'provider' => 'UsernamePassword',
				'patternType' => '',
				'patternValue' => ''
			),
			array(
				'provider' => 'F3_TestPackage_TestAuthenticationProvider',
				'patternType' => 'URL',
				'patternValue' => '/some/url/.*'
			)
		);

		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue($configuration));

		$providerManager = new F3_FLOW3_Security_Authentication_ProviderManager($mockConfigurationManager, $this->componentFactory, new F3_FLOW3_Security_Authentication_ProviderResolver($this->componentManager), new F3_FLOW3_Security_RequestPatternResolver($this->componentManager));
		$providers = $providerManager->getProviders();
		$tokens = $providerManager->getTokens();

		$this->assertType('F3_FLOW3_Security_Authentication_Provider_UsernamePassword', $providers[0]);
		$this->assertType('F3_TestPackage_TestAuthenticationProvider', $providers[1]);

		$this->assertType('F3_FLOW3_Security_Authentication_Token_UsernamePassword', $tokens[0]);
		$this->assertType('F3_TestPackage_TestAuthenticationToken', $tokens[1]);

		$this->assertTrue($tokens[1]->hasRequestPattern());
		$this->assertType('F3_FLOW3_Security_RequestPattern_URL', $tokens[1]->getRequestPattern());
		$this->assertEquals('/some/url/.*', $tokens[1]->getRequestPattern()->getPattern());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateDelegatesAuthenticationToTheCorrectProvidersInTheCorrectOrder() {
		$mockProvider1 = $this->getMock('F3_FLOW3_Security_Authentication_ProviderInterface', array(), array(), 'mockAuthenticationProvider1');
		$mockProvider2 = $this->getMock('F3_FLOW3_Security_Authentication_ProviderInterface', array(), array(), 'mockAuthenticationProvider2');
		$mockToken1 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface', array(), array(), 'mockAuthenticationToken1');
		$mockToken2 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface', array(), array(), 'mockAuthenticationToken2');

		$mockProvider1->expects($this->atLeastOnce())->method('canAuthenticate')->will($this->onConsecutiveCalls(TRUE, FALSE));
		$mockProvider2->expects($this->atLeastOnce())->method('canAuthenticate')->will($this->returnValue(TRUE));

		$mockProvider1->expects($this->once())->method('authenticate')->with($mockToken1);
		$mockProvider2->expects($this->once())->method('authenticate')->with($mockToken2);

		$mockConfigurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array(), array(), '', FALSE);
		$configuration = new F3_FLOW3_Configuration_Container();
		$configuration->security->authentication->providers = array();

		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->will($this->returnValue($configuration));

		$providerManager = new F3_FLOW3_Security_Authentication_ProviderManager($mockConfigurationManager, $this->componentFactory, new F3_FLOW3_Security_Authentication_ProviderResolver($this->componentManager), new F3_FLOW3_Security_RequestPatternResolver($this->componentManager));
		$providerManager->setProviders(array($mockProvider1, $mockProvider2));

		$providerManager->authenticate($mockToken1);
		$providerManager->authenticate($mockToken2);
	}
}
?>