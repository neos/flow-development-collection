<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authentication;

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
 * Testcase for authentication provider manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ProviderManagerTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredProvidersAndTokensAreBuiltCorrectly() {
		$securityContext = $this->getMock('F3::FLOW3::Security::Context', array(), array(), '', FALSE);
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = new F3::FLOW3::Configuration::Container();
		$settings->security->authentication->providers = array(
			array(
				'provider' => 'UsernamePassword',
				'patternType' => '',
				'patternValue' => ''
			),
			array(
				'provider' => 'F3::TestPackage::TestAuthenticationProvider',
				'patternType' => 'URL',
				'patternValue' => '/some/url/.*'
			)
		);

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$providerManager = new F3::FLOW3::Security::Authentication::ProviderManager($mockConfigurationManager, $this->componentFactory, new F3::FLOW3::Security::Authentication::ProviderResolver($this->componentManager), new F3::FLOW3::Security::RequestPatternResolver($this->componentManager));
		$providers = $providerManager->getProviders();
		$tokens = $providerManager->getTokens();

		$this->assertType('F3::FLOW3::Security::Authentication::Provider::UsernamePassword', $providers[0]);
		$this->assertType('F3::TestPackage::TestAuthenticationProvider', $providers[1]);

		$this->assertType('F3::FLOW3::Security::Authentication::Token::UsernamePassword', $tokens[0]);
		$this->assertType('F3::TestPackage::TestAuthenticationToken', $tokens[1]);

		$this->assertTrue($tokens[1]->hasRequestPattern());
		$this->assertType('F3::FLOW3::Security::RequestPattern::URL', $tokens[1]->getRequestPattern());
		$this->assertEquals('/some/url/.*', $tokens[1]->getRequestPattern()->getPattern());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateDelegatesAuthenticationToTheCorrectProvidersInTheCorrectOrder() {
		$securityContext = $this->getMock('F3::FLOW3::Security::Context', array(), array(), '', FALSE);
		$mockProvider1 = $this->getMock('F3::FLOW3::Security::Authentication::ProviderInterface', array(), array(), 'mockAuthenticationProvider1');
		$mockProvider2 = $this->getMock('F3::FLOW3::Security::Authentication::ProviderInterface', array(), array(), 'mockAuthenticationProvider2');
		$mockToken1 = $this->getMock('F3::FLOW3::Security::Authentication::TokenInterface', array(), array(), 'mockAuthenticationToken1');
		$mockToken2 = $this->getMock('F3::FLOW3::Security::Authentication::TokenInterface', array(), array(), 'mockAuthenticationToken2');

		$mockToken1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$mockToken2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$mockProvider1->expects($this->atLeastOnce())->method('canAuthenticate')->will($this->onConsecutiveCalls(TRUE, FALSE));
		$mockProvider2->expects($this->atLeastOnce())->method('canAuthenticate')->will($this->returnValue(TRUE));

		$mockProvider1->expects($this->once())->method('authenticate')->with($mockToken1);
		$mockProvider2->expects($this->once())->method('authenticate')->with($mockToken2);

		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = new F3::FLOW3::Configuration::Container();
		$settings->security->authentication->providers = array();

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));
		$securityContext->expects($this->atLeastOnce())->method('authenticateAllTokens')->will($this->returnValue(TRUE));
		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken1, $mockToken2)));

		$providerManager = new F3::FLOW3::Security::Authentication::ProviderManager($mockConfigurationManager, $this->componentFactory, new F3::FLOW3::Security::Authentication::ProviderResolver($this->componentManager), new F3::FLOW3::Security::RequestPatternResolver($this->componentManager));
		$providerManager->setProviders(array($mockProvider1, $mockProvider2));
		$providerManager->setSecurityContext($securityContext);

		$providerManager->authenticate();
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateSetsTheAuthenticationPerformedFlagInTheSecurityContextCorrectly() {
		$securityContext = $this->getMock('F3::FLOW3::Security::Context', array(), array(), '', FALSE);
		$mockToken1 = $this->getMock('F3::FLOW3::Security::Authentication::TokenInterface');
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = new F3::FLOW3::Configuration::Container();
		$settings->security->authentication->providers = array();

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array()));
		$securityContext->expects($this->once())->method('setAuthenticationPerformed')->with(TRUE);

		$providerManager = new F3::FLOW3::Security::Authentication::ProviderManager($mockConfigurationManager, $this->componentFactory, new F3::FLOW3::Security::Authentication::ProviderResolver($this->componentManager), new F3::FLOW3::Security::RequestPatternResolver($this->componentManager));
		$providerManager->setSecurityContext($securityContext);
		$providerManager->setProviders(array());

		$providerManager->authenticate($mockToken1);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateTriesToAuthenticateAnActiveToken() {
		$context = $this->getMock('F3::FLOW3::Security::Context', array(), array(), '', FALSE);
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = new F3::FLOW3::Configuration::Container();
		$settings->security->authentication->providers = array();

		$token1 = $this->getMock('F3::FLOW3::Security::Authentication::TokenInterface');
		$token2 = $this->getMock('F3::FLOW3::Security::Authentication::TokenInterface');

		$token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$context->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$providerManager = new F3::FLOW3::Security::Authentication::ProviderManager($mockConfigurationManager, $this->componentFactory, new F3::FLOW3::Security::Authentication::ProviderResolver($this->componentManager), new F3::FLOW3::Security::RequestPatternResolver($this->componentManager));
		$providerManager->setSecurityContext($context);
		$providerManager->setProviders(array());

		$providerManager->authenticate();
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateThrowsAnExceptionIfNoTokenCouldBeAuthenticated() {
		$context = $this->getMock('F3::FLOW3::Security::Context', array(), array(), '', FALSE);
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = new F3::FLOW3::Configuration::Container();
		$settings->security->authentication->providers = array();

		$token1 = $this->getMock('F3::FLOW3::Security::Authentication::TokenInterface');
		$token2 = $this->getMock('F3::FLOW3::Security::Authentication::TokenInterface');

		$token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$context->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$providerManager = new F3::FLOW3::Security::Authentication::ProviderManager($mockConfigurationManager, $this->componentFactory, new F3::FLOW3::Security::Authentication::ProviderResolver($this->componentManager), new F3::FLOW3::Security::RequestPatternResolver($this->componentManager));
		$providerManager->setSecurityContext($context);
		$providerManager->setProviders(array());

		try {
			$providerManager->authenticate();
			$this->fail('No exception has been thrown.');
		} catch (F3::FLOW3::Security::Exception::AuthenticationRequired $exception) {}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateThrowsAnExceptionIfAuthenticateAllTokensIsTrueButATokenCouldNotBeAuthenticated() {
		$context = $this->getMock('F3::FLOW3::Security::Context', array(), array(), '', FALSE);
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = new F3::FLOW3::Configuration::Container();
		$settings->security->authentication->providers = array();

		$token1 = $this->getMock('F3::FLOW3::Security::Authentication::TokenInterface');
		$token2 = $this->getMock('F3::FLOW3::Security::Authentication::TokenInterface');

		$token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$context->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));
		$context->expects($this->atLeastOnce())->method('authenticateAllTokens')->will($this->returnValue(TRUE));

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$providerManager = new F3::FLOW3::Security::Authentication::ProviderManager($mockConfigurationManager, $this->componentFactory, new F3::FLOW3::Security::Authentication::ProviderResolver($this->componentManager), new F3::FLOW3::Security::RequestPatternResolver($this->componentManager));
		$providerManager->setSecurityContext($context);
		$providerManager->setProviders(array());

		try {
			$providerManager->authenticate();
			$this->fail('No exception has been thrown.');
		} catch (F3::FLOW3::Security::Exception::AuthenticationRequired $exception) {}
	}
}
?>