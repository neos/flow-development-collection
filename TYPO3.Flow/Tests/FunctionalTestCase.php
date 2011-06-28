<?php
namespace F3\FLOW3\Tests;

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
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A base test case for functional tests
 *
 * Subclass this base class if you want to take advantage of the framework
 * capabilities, for example are in need of the object manager.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
abstract class FunctionalTestCase extends \F3\FLOW3\Tests\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Core\Bootstrap
	 */
	protected static $flow3;

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = FALSE;

	/**
	 * @var \F3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = FALSE;

	/**
	 * @var \F3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * @var \F3\FLOW3\Security\Authentication\Provider\TestingProvider
	 */
	protected $testingProvider;

	/**
	 * Initialize FLOW3
	 *
	 * @return void
	 */
	static public function setUpBeforeClass() {
		self::$flow3 = \F3\FLOW3\Core\Bootstrap::$staticObjectManager->get('F3\FLOW3\Core\Bootstrap');

		if (static::$testablePersistenceEnabled === TRUE) {
			self::$flow3->getObjectManager()->get('F3\FLOW3\Persistence\PersistenceManagerInterface')->initialize();
			if (is_callable(array(self::$flow3->getObjectManager()->get('F3\FLOW3\Persistence\PersistenceManagerInterface'), 'compile'))) {
				$result = self::$flow3->getObjectManager()->get('F3\FLOW3\Persistence\PersistenceManagerInterface')->compile();
				if ($result === FALSE) {
					self::markTestSkipped('Test skipped because setting up the persistence failed.');
				}
			}
		}
	}

	/**
	 * Tear down FLOW3
	 *
	 * @return void
	 */
	static public function tearDownAfterClass() {
		$persistenceManager = self::$flow3->getObjectManager()->get('F3\FLOW3\Persistence\PersistenceManagerInterface');
		if (is_callable(array($persistenceManager, 'tearDown'))) {
			$persistenceManager->tearDown();
		}
		self::$flow3 = NULL;
	}

	/**
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function runBare() {
		$this->objectManager = self::$flow3->getObjectManager();
		parent::runBare();
	}

	/**
	 * Enables security tests for this testcase
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function enableTestableSecurity() {
		$this->testableSecurityEnabled = TRUE;
	}

	/**
	 * Sets up test requirements depending on the enabled tests
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setUp() {
		if ($this->testableSecurityEnabled === TRUE) {
			$this->setupSecurity();
		}
		if (static::$testablePersistenceEnabled === TRUE) {
			$this->persistenceManager = $this->objectManager->get('F3\FLOW3\Persistence\PersistenceManagerInterface');
		}
	}

	/**
	 * Sets up security test requirements
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function setupSecurity() {
		$this->accessDecisionManager = $this->objectManager->get('F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$this->accessDecisionManager->setOverrideDecision(NULL);

		$this->testingProvider = $this->objectManager->get('F3\FLOW3\Security\Authentication\Provider\TestingProvider');
		$this->testingProvider->setName('DefaultProvider');

		$this->securityContext = $this->objectManager->get('F3\FLOW3\Security\Context');
		$request = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$this->securityContext->initialize($request);
	}

	/**
	 * Tears down test requirements depending on the enabled tests
	 *
	 * Note: tearDown() is also called if an exception occurred in one of the tests. If the problem is caused by
	 *       some security or persistence related part of FLOW3, the error might be hard to track because their
	 *       specialized tearDown() methods might cause fatal errors. In those cases just output the original
	 *       exception message by adding an echo($this->statusMessage) as the first line of this method.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function tearDown() {
		if ($this->testableSecurityEnabled === TRUE) {
			$this->tearDownSecurity();
		}
		if (static::$testablePersistenceEnabled === TRUE) {
			$this->persistenceManager->persistAll();
		}
	}

	/**
	 * Resets security test requirements
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function tearDownSecurity() {
		$this->accessDecisionManager->reset();
		$this->testingProvider->reset();
	}

	/**
	 * Calls the given action of the given controller
	 *
	 * @param string $controllerName The name of the controller to be called
	 * @param string $controllerPackageKey The package key the controller resides in
	 * @param string $controllerActionName The name of the action to be called, e.g. 'index'
	 * @param array $arguments Optional arguments passed to controller
	 * @param string $format The request format, defaults to 'html'
	 * @return string The result of the controller action
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function sendWebRequest($controllerName, $controllerPackageKey, $controllerActionName, array $arguments = array(), $format = 'html') {
		if (!getenv('FLOW3_REWRITEURLS')) {
				// Simulate the use of mod_rewrite for the test.
			putenv('FLOW3_REWRITEURLS=1');
		}

			// Initialize the routes
		$configurationManager = $this->objectManager->get('F3\FLOW3\Configuration\ConfigurationManager');
		$routesConfiguration = $configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
		$router = $this->objectManager->get('F3\FLOW3\MVC\Web\Routing\Router');
		$router->setRoutesConfiguration($routesConfiguration);

			// Build up Mock request behaving like the real one.
		$controller = $this->objectManager->get('TYPO3\\' . $controllerPackageKey . '\\Controller\\' . $controllerName . 'Controller');

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue($controllerPackageKey));
		$mockRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
		$mockRequest->expects($this->any())->method('getControllerName')->will($this->returnValue($controllerName));
		$mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue($arguments));
		$mockRequest->expects($this->any())->method('getArgument')->will($this->returnCallback(function($argumentName) use ($arguments) {
			return $arguments[$argumentName];
		}));
		$mockRequest->expects($this->any())->method('hasArgument')->will($this->returnCallback(function($argumentName) use ($arguments) {
			return isset($arguments[$argumentName]);
		}));
		$mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue($format));
		$mockRequest->expects($this->any())->method('getBaseUri')->will($this->returnValue('http://baseUri/'));
		$mockRequest->expects($this->any())->method('getOriginalRequestMappingResults')->will($this->returnValue(new \F3\FLOW3\Error\Result()));

			// Build up Mock response collecting the output.
		$mockResponse = $this->getMock('F3\FLOW3\MVC\ResponseInterface', array(), array(), '', FALSE);

		$content = '';
		$mockResponse->expects($this->any())->method('appendContent')->will($this->returnCallback(function($newContent) use(&$content) {
			$content .= $newContent;
		}));

		$controller->processRequest($mockRequest, $mockResponse);

		return $content;
	}

	/**
	 * Creates a new account, assigns it the given roles and authenticates it.
	 * The created account is returned for further modification, for example for attaching a Party object to it.
	 *
	 * @param array $roleNames A list of roles the new account should have
	 * @return \F3\FLOW3\Security\Account The created account
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function authenticateRoles(array $roleNames) {
		$account = new \F3\FLOW3\Security\Account();
		$roles = array();
		foreach ($roleNames as $roleName) {
			$roles[] = new \F3\FLOW3\Security\Policy\Role($roleName);
		}
		$account->setRoles($roles);

		$this->testingProvider->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
		$this->testingProvider->setAccount($account);

		$request = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$this->securityContext->initialize($request);

		$authenticationProviderManager = $this->objectManager->get('F3\FLOW3\Security\Authentication\AuthenticationProviderManager');
		$authenticationProviderManager->authenticate();

		return $account;
	}

	/**
	 * Disables authorization for the current test
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function disableAuthorization() {
		$this->accessDecisionManager->setOverrideDecision(TRUE);
	}

}
?>