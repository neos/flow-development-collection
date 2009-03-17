<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication;

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
 * Testcase for authentication provider manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ProviderManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredProvidersAndTokensAreBuiltCorrectly() {
		$securityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$settings = array();
		$settings['security']['authentication']['providers'] = array(
			array(
				'type' => 'UsernamePassword',
				'requestPatterns' => array(
					'URI' => 'typo3/.*',
					'F3\TestPackage\TestRequestPattern' => 'test',
				),
			),
			array(
				'type' => 'F3\TestPackage\TestAuthenticationProvider',
				'requestPatterns' => array(
					'URI' => 'fe/.*',
				),
			)
		);

		$providerManager = new \F3\FLOW3\Security\Authentication\ProviderManager($this->objectManager, new \F3\FLOW3\Security\Authentication\ProviderResolver($this->objectManager), new \F3\FLOW3\Security\RequestPatternResolver($this->objectManager), new \F3\FLOW3\Security\Authentication\EntryPointResolver($this->objectManager));
		$providerManager->injectSettings($settings);

		$providers = $providerManager->getProviders();
		$tokens = $providerManager->getTokens();

		$this->assertType('F3\FLOW3\Security\Authentication\Provider\UsernamePassword', $providers[0]);
		$this->assertType('F3\TestPackage\TestAuthenticationProvider', $providers[1]);

		$this->assertType('F3\FLOW3\Security\Authentication\Token\UsernamePassword', $tokens[0]);
		$this->assertType('F3\TestPackage\TestAuthenticationToken', $tokens[1]);

		$this->assertTrue($tokens[1]->hasRequestPatterns());

		$patterns = $tokens[1]->getRequestPatterns();

		$this->assertType('F3\FLOW3\Security\RequestPattern\URI', $patterns[0]);
		$this->assertEquals('fe/.*', $patterns[0]->getPattern());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateDelegatesAuthenticationToTheCorrectProvidersInTheCorrectOrder() {
		$securityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockProvider1 = $this->getMock('F3\FLOW3\Security\Authentication\ProviderInterface', array(), array(), 'mockAuthenticationProvider1');
		$mockProvider2 = $this->getMock('F3\FLOW3\Security\Authentication\ProviderInterface', array(), array(), 'mockAuthenticationProvider2');
		$mockToken1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'mockAuthenticationToken1');
		$mockToken2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'mockAuthenticationToken2');

		$mockToken1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$mockToken2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$mockToken1->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED));
		$mockToken2->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED));

		$mockProvider1->expects($this->atLeastOnce())->method('canAuthenticate')->will($this->onConsecutiveCalls(TRUE, FALSE));
		$mockProvider2->expects($this->atLeastOnce())->method('canAuthenticate')->will($this->returnValue(TRUE));

		$mockProvider1->expects($this->once())->method('authenticate')->with($mockToken1);
		$mockProvider2->expects($this->once())->method('authenticate')->with($mockToken2);

		$settings = array();
		$settings['security']['authentication']['providers'] = array();

		$securityContext->expects($this->atLeastOnce())->method('authenticateAllTokens')->will($this->returnValue(TRUE));
		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken1, $mockToken2)));

		$providerManager = new \F3\FLOW3\Security\Authentication\ProviderManager($this->objectManager, new \F3\FLOW3\Security\Authentication\ProviderResolver($this->objectManager), new \F3\FLOW3\Security\RequestPatternResolver($this->objectManager), new \F3\FLOW3\Security\Authentication\EntryPointResolver($this->objectManager));
		$providerManager->injectSettings($settings);
		$providerManager->setProviders(array($mockProvider1, $mockProvider2));
		$providerManager->setSecurityContext($securityContext);

		$providerManager->authenticate();
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateTriesToAuthenticateAnActiveToken() {
		$context = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$settings = array();
		$settings['security']['authentication']['providers'] = array();

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$context->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$settings = array();
		$settings['security']['authentication']['providers'] = array();

		$providerManager = new \F3\FLOW3\Security\Authentication\ProviderManager($this->objectManager, new \F3\FLOW3\Security\Authentication\ProviderResolver($this->objectManager), new \F3\FLOW3\Security\RequestPatternResolver($this->objectManager), new \F3\FLOW3\Security\Authentication\EntryPointResolver($this->objectManager));
		$providerManager->injectSettings($settings);
		$providerManager->setSecurityContext($context);
		$providerManager->setProviders(array());

		$providerManager->authenticate();
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateAuthenticatesOnlyTokensWithStatusAuthenticationNeeded() {
		$securityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockProvider = $this->getMock('F3\FLOW3\Security\Authentication\ProviderInterface');
		$mockToken1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'mockAuthenticationToken11');
		$mockToken2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'mockAuthenticationToken12');
		$mockToken3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'mockAuthenticationToken13');

		$mockToken1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$mockToken2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$mockToken3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$mockToken1->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(\F3\FLOW3\Security\Authentication\TokenInterface::WRONG_CREDENTIALS));
		$mockToken2->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(\F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN));
		$mockToken3->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED));

		$mockProvider->expects($this->any())->method('canAuthenticate')->will($this->returnValue(TRUE));
		$mockProvider->expects($this->once())->method('authenticate')->with($mockToken3);

		$settings = array();
		$settings['security']['authentication']['providers'] = array();

		$securityContext->expects($this->atLeastOnce())->method('authenticateAllTokens')->will($this->returnValue(FALSE));
		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken1, $mockToken2, $mockToken3)));

		$providerManager = new \F3\FLOW3\Security\Authentication\ProviderManager($this->objectManager, new \F3\FLOW3\Security\Authentication\ProviderResolver($this->objectManager), new \F3\FLOW3\Security\RequestPatternResolver($this->objectManager), new \F3\FLOW3\Security\Authentication\EntryPointResolver($this->objectManager));
		$providerManager->injectSettings($settings);
		$providerManager->setSecurityContext($securityContext);
		$providerManager->setProviders(array($mockProvider));

		$providerManager->authenticate();
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateThrowsAnExceptionIfNoTokenCouldBeAuthenticated() {
		$context = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$settings = array();
		$settings['security']['authentication']['providers'] = array();

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$context->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$providerManager = new \F3\FLOW3\Security\Authentication\ProviderManager($this->objectManager, new \F3\FLOW3\Security\Authentication\ProviderResolver($this->objectManager), new \F3\FLOW3\Security\RequestPatternResolver($this->objectManager), new \F3\FLOW3\Security\Authentication\EntryPointResolver($this->objectManager));
		$providerManager->injectSettings($settings);
		$providerManager->setSecurityContext($context);
		$providerManager->setProviders(array());

		try {
			$providerManager->authenticate();
			$this->fail('No exception has been thrown.');
		} catch (\F3\FLOW3\Security\Exception\AuthenticationRequired $exception) {}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateThrowsAnExceptionIfAuthenticateAllTokensIsTrueButATokenCouldNotBeAuthenticated() {
		$context = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$settings = array();
		$settings['security']['authentication']['providers'] = array();

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$context->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));
		$context->expects($this->atLeastOnce())->method('authenticateAllTokens')->will($this->returnValue(TRUE));

		$providerManager = new \F3\FLOW3\Security\Authentication\ProviderManager($this->objectManager, new \F3\FLOW3\Security\Authentication\ProviderResolver($this->objectManager), new \F3\FLOW3\Security\RequestPatternResolver($this->objectManager), new \F3\FLOW3\Security\Authentication\EntryPointResolver($this->objectManager));
		$providerManager->injectSettings($settings);
		$providerManager->setSecurityContext($context);
		$providerManager->setProviders(array());

		try {
			$providerManager->authenticate();
			$this->fail('No exception has been thrown.');
		} catch (\F3\FLOW3\Security\Exception\AuthenticationRequired $exception) {}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredAuthenticationEntryPointsAreInstalledCorrectly() {
		$securityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$settings = array();
		$settings['security']['authentication']['providers'] = array(
			array(
				'type' => 'UsernamePassword',
				'entryPoint' => array(
					'type' => 'WebRedirect',
					'options' => array(
						'firstConfigurationParameter' => 1,
						'secondConfigurationParameter' => 2,
						'thirdConfigurationParameter' => 3,
					)
				)
			)
		);

		$providerManager = new \F3\FLOW3\Security\Authentication\ProviderManager($this->objectManager, new \F3\FLOW3\Security\Authentication\ProviderResolver($this->objectManager), new \F3\FLOW3\Security\RequestPatternResolver($this->objectManager), new \F3\FLOW3\Security\Authentication\EntryPointResolver($this->objectManager));
		$providerManager->injectSettings($settings);
		$tokens = $providerManager->getTokens();

		$entryPoint = $tokens[0]->getAuthenticationEntryPoint();

		$this->assertType('F3\FLOW3\Security\Authentication\EntryPoint\WebRedirect', $entryPoint, 'The configured token has not been resolved');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function optionsOfTheConfiguredAuthenticationEntryPointsAreSetCorrectly() {
		$this->markTestIncomplete();
	}
}
?>