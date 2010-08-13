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
 * Testcase for authentication provider manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AuthenticationProviderManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredProvidersAndTokensAreBuiltCorrectly() {
		$mockToken1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken1->expects($this->once())->method('setAuthenticationProviderName')->with('MyProvider');
		$mockToken2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken2->expects($this->once())->method('setAuthenticationProviderName')->with('AnotherProvider');

		$mockProvider1 = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderInterface', array(), array(), '', FALSE);
		$mockProvider1->expects($this->any())->method('getTokenClassNames')->will($this->returnValue(array('token1')));
		$mockProvider2 = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderInterface', array(), array(), '', FALSE);
		$mockProvider2->expects($this->any())->method('getTokenClassNames')->will($this->returnValue(array('token2')));

		$resolveProviderClassCallback = function() {
			$args = func_get_args();

			if ($args[0] === 'UsernamePassword') return 'provider1';
			elseif ($args[0] === 'F3\TestAuthenticationProvider') return 'provider2';
		};

		$getObjectCallback = function() use (&$mockProvider1, &$mockProvider2, &$mockToken1, &$mockToken2) {
			$args = func_get_args();

			if ($args[0] === 'provider1' && $args[1] == 'MyProvider' && $args[2] == array('provider1options')) return $mockProvider1;
			elseif ($args[0] === 'provider2' && $args[1] == 'AnotherProvider' && $args[2] == array('provider2options')) return $mockProvider2;
			elseif ($args[0] === 'token1') return $mockToken1;
			elseif ($args[0] === 'token2') return $mockToken2;
		};

		$mockProviderResolver = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderResolver', array(), array(), '', FALSE);
		$mockProviderResolver->expects($this->any())->method('resolveProviderClass')->will($this->returnCallback($resolveProviderClassCallback));
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback($getObjectCallback));

		$providerConfiguration = array(
			'MyProvider' => array(
				'providerClass' => 'UsernamePassword',
				'options' => array('provider1options')
			),
			'AnotherProvider' => array(
				'providerClass' => 'F3\TestAuthenticationProvider',
				'options' => array('provider2options')
			),
		);

		$mockProviderManager = $this->getAccessibleMock('F3\FLOW3\Security\Authentication\AuthenticationProviderManager', array('authenticate'), array(), '', FALSE);
		$mockProviderManager->_set('objectManager', $mockObjectManager);
		$mockProviderManager->_set('providerResolver', $mockProviderResolver);

		$mockProviderManager->_call('buildProvidersAndTokensFromConfiguration', $providerConfiguration);

		$providers = $mockProviderManager->_get('providers');
		$tokens = $mockProviderManager->_get('tokens');
		$expectedProviders = array($mockProvider1, $mockProvider2);
		$expectedTokens = array($mockToken1, $mockToken2);

		$this->assertEquals($expectedProviders, $providers, 'The wrong providers were created.');
		$this->assertEquals($expectedTokens, $tokens, 'The wrong tokens were created.');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredRequestPatternsAreSetCorrectlyInAToken() {
		$mockPattern1 = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), '', FALSE);
		$mockPattern1->expects($this->once())->method('setPattern')->with('typo3/.*');
		$mockPattern2 = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), '', FALSE);
		$mockPattern2->expects($this->once())->method('setPattern')->with('test');

		$mockProvider1 = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderInterface', array(), array(), '', FALSE);
		$mockProvider1->expects($this->any())->method('getTokenClassNames')->will($this->returnValue(array('token1')));
		$mockToken1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken1->expects($this->once())->method('setRequestPatterns')->with($this->equalTo(array($mockPattern1, $mockPattern2)));

		$getObjectCallback = function() use (&$mockProvider1, &$mockToken1, &$mockPattern1, &$mockPattern2) {
			$args = func_get_args();

			if ($args[0] === 'provider1') return $mockProvider1;
			elseif ($args[0] === 'token1') return $mockToken1;
			elseif ($args[0] === 'mockPatternURI') return $mockPattern1;
			elseif ($args[0] === 'mockPatternTest') return $mockPattern2;
		};

		$resolveRequestPatternClassCallback = function() {
			$args = func_get_args();

			if ($args[0] === 'URI') return 'mockPatternURI';
			elseif ($args[0] === 'F3\TestRequestPattern') return 'mockPatternTest';
		};

		$mockPatternResolver = $this->getMock('F3\FLOW3\Security\RequestPatternResolver', array(), array(), '', FALSE);
		$mockPatternResolver->expects($this->any())->method('resolveRequestPatternClass')->will($this->returnCallback($resolveRequestPatternClassCallback));
		$mockProviderResolver = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderResolver', array(), array(), '', FALSE);
		$mockProviderResolver->expects($this->any())->method('resolveProviderClass')->will($this->returnValue('provider1'));
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback($getObjectCallback));

		$providerConfiguration = array(
			'MyProvider' => array(
				'providerClass' => 'UsernamePassword',
				'requestPatterns' => array(
					'URI' => 'typo3/.*',
					'F3\TestRequestPattern' => 'test',
				),
			),
		);

		$mockProviderManager = $this->getAccessibleMock('F3\FLOW3\Security\Authentication\AuthenticationProviderManager', array('authenticate'), array(), '', FALSE);
		$mockProviderManager->_set('objectManager', $mockObjectManager);
		$mockProviderManager->_set('providerResolver', $mockProviderResolver);
		$mockProviderManager->_set('requestPatternResolver', $mockPatternResolver);

		$mockProviderManager->_call('buildProvidersAndTokensFromConfiguration', $providerConfiguration);

		$providers = $mockProviderManager->_get('providers');
		$tokens = $mockProviderManager->_get('tokens');
		$expectedProviders = array($mockProvider1);
		$expectedTokens = array($mockToken1);

		$this->assertEquals($expectedProviders, $providers, 'The wrong providers were created.');
		$this->assertEquals($expectedTokens, $tokens, 'The wrong tokens were created.');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredAuthenticationEntryPointIsInstalledCorrectly() {
		$mockEntryPoint = $this->getMock('F3\FLOW3\Security\Authentication\EntryPointInterface', array(), array(), '', FALSE);
		$mockEntryPoint->expects($this->once())->method('setOptions')->with($this->equalTo(array('first' => 1, 'second' => 2, 'third' => 3,)));

		$mockProvider1 = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderInterface', array(), array(), '', FALSE);
		$mockProvider1->expects($this->any())->method('getTokenClassNames')->will($this->returnValue(array('token1')));
		$mockToken1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken1->expects($this->once())->method('setAuthenticationEntryPoint')->with($this->equalTo($mockEntryPoint));

		$getObjectCallback = function() use (&$mockProvider1, &$mockToken1, &$mockEntryPoint) {
			$args = func_get_args();

			if ($args[0] === 'provider1') return $mockProvider1;
			elseif ($args[0] === 'token1') return $mockToken1;
			elseif ($args[0] === 'entryPoint') return $mockEntryPoint;
		};

		$mockEntryPointResolver = $this->getMock('F3\FLOW3\Security\Authentication\EntryPointResolver', array(), array(), '', FALSE);
		$mockEntryPointResolver->expects($this->any())->method('resolveEntryPointClass')->will($this->returnValue('entryPoint'));
		$mockProviderResolver = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderResolver', array(), array(), '', FALSE);
		$mockProviderResolver->expects($this->any())->method('resolveProviderClass')->will($this->returnValue('provider1'));
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback($getObjectCallback));

		$providerConfiguration = array(
			'MyProvider' => array(
				'providerClass' => 'UsernamePassword',
				'entryPoint' => array(
					'WebRedirect' => array(
						'first' => 1,
						'second' => 2,
						'third' => 3,
					)
				)
			)
		);

		$mockProviderManager = $this->getAccessibleMock('F3\FLOW3\Security\Authentication\AuthenticationProviderManager', array('authenticate'), array(), '', FALSE);
		$mockProviderManager->_set('objectManager', $mockObjectManager);
		$mockProviderManager->_set('providerResolver', $mockProviderResolver);
		$mockProviderManager->_set('entryPointResolver', $mockEntryPointResolver);

		$mockProviderManager->_call('buildProvidersAndTokensFromConfiguration', $providerConfiguration);

		$providers = $mockProviderManager->_get('providers');
		$tokens = $mockProviderManager->_get('tokens');
		$expectedProviders = array($mockProvider1);
		$expectedTokens = array($mockToken1);

		$this->assertEquals($expectedProviders, $providers, 'The wrong providers were created.');
		$this->assertEquals($expectedTokens, $tokens, 'The wrong tokens were created.');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateDelegatesAuthenticationToTheCorrectProvidersInTheCorrectOrder() {
		$securityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockProvider1 = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderInterface', array(), array(), 'mockAuthenticationProvider1');
		$mockProvider2 = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderInterface', array(), array(), 'mockAuthenticationProvider2');
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

		$securityContext->expects($this->atLeastOnce())->method('authenticateAllTokens')->will($this->returnValue(TRUE));
		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken1, $mockToken2)));

		$mockProviderManager = $this->getAccessibleMock('F3\FLOW3\Security\Authentication\AuthenticationProviderManager', array('dummy'), array(), '', FALSE);
		$mockProviderManager->_set('providers', array($mockProvider1, $mockProvider2));
		$mockProviderManager->_set('securityContext', $securityContext);

		$mockProviderManager->authenticate();
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateAuthenticatesOnlyTokensWithStatusAuthenticationNeeded() {
		$securityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockProvider = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderInterface');
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

		$securityContext->expects($this->atLeastOnce())->method('authenticateAllTokens')->will($this->returnValue(FALSE));
		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken1, $mockToken2, $mockToken3)));

		$mockProviderManager = $this->getAccessibleMock('F3\FLOW3\Security\Authentication\AuthenticationProviderManager', array('dummy'), array(), '', FALSE);
		$mockProviderManager->_set('providers', array($mockProvider));
		$mockProviderManager->_set('securityContext', $securityContext);

		$mockProviderManager->authenticate();
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException F3\FLOW3\Security\Exception\AuthenticationRequiredException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateThrowsAnExceptionIfNoTokenCouldBeAuthenticated() {
		$securityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$mockProviderManager = $this->getAccessibleMock('F3\FLOW3\Security\Authentication\AuthenticationProviderManager', array('dummy'), array(), '', FALSE);
		$mockProviderManager->_set('providers', array());
		$mockProviderManager->_set('securityContext', $securityContext);

		$mockProviderManager->authenticate();
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException F3\FLOW3\Security\Exception\AuthenticationRequiredException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateThrowsAnExceptionIfAuthenticateAllTokensIsTrueButATokenCouldNotBeAuthenticated() {
		$securityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));
		$securityContext->expects($this->atLeastOnce())->method('authenticateAllTokens')->will($this->returnValue(TRUE));

		$mockProviderManager = $this->getAccessibleMock('F3\FLOW3\Security\Authentication\AuthenticationProviderManager', array('dummy'), array(), '', FALSE);
		$mockProviderManager->_set('providers', array());
		$mockProviderManager->_set('securityContext', $securityContext);

		$mockProviderManager->authenticate();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function logoutSetsTheAuthenticationStatusOfAllActiveAuthenticationTokensToNoCredentialsGiven() {
		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->once())->method('setAuthenticationStatus')->with(\F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->once())->method('setAuthenticationStatus')->with(\F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);

		$authenticationTokens = array($token1, $token2);

		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue($authenticationTokens));

		$mockProviderManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderManager', array('dummy'), array(), '', FALSE);
		$mockProviderManager->setSecurityContext($mockContext);

		$mockProviderManager->logout();
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function noTokensAndProvidersAreBuiltIfTheConfigurationArrayIsEmpty() {
		$mockProviderManager = $this->getAccessibleMock('F3\FLOW3\Security\Authentication\AuthenticationProviderManager', array('authenticate'), array(), '', FALSE);
		$mockProviderManager->_call('buildProvidersAndTokensFromConfiguration', array());

		$providers = $mockProviderManager->_get('providers');
		$tokens = $mockProviderManager->_get('tokens');

		$this->assertEquals(array(), $providers, 'The array of providers should be empty.');
		$this->assertEquals(array(), $tokens, 'The array of tokens should be empty.');
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException F3\FLOW3\Security\Exception\InvalidAuthenticationProviderException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function anExceptionIsThrownIfTheConfiguredProviderDoesNotExist() {
		$providerConfiguration = array(
			'NotExistingProvider' => array(
				'providerClass' => 'NotExistingProviderClass'
			),
		);

		$mockProviderResolver = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationProviderResolver', array(), array(), '', FALSE);
		$mockProviderResolver->expects($this->once())->method('resolveProviderClass')->will($this->returnValue(NULL));

		$mockProviderManager = $this->getAccessibleMock('F3\FLOW3\Security\Authentication\AuthenticationProviderManager', array('authenticate'), array(), '', FALSE);
		$mockProviderManager->_set('providerResolver', $mockProviderResolver);

		$mockProviderManager->_call('buildProvidersAndTokensFromConfiguration', $providerConfiguration);
	}
}
?>