<?php
namespace TYPO3\Flow\Tests;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Routing\Route;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\Files;

/**
 * A base test case for functional tests
 *
 * Subclass this base class if you want to take advantage of the framework
 * capabilities, for example are in need of the object manager.
 *
 * @api
 */
abstract class FunctionalTestCase extends \TYPO3\Flow\Tests\BaseTestCase {

	/**
	 * A functional instance of the Object Manager, for use in concrete test cases.
	 *
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @api
	 */
	protected $objectManager;

	/**
	 * @var Bootstrap
	 * @api
	 */
	protected static $bootstrap;

	/**
	 * If enabled, this test case will modify the behavior of the security framework
	 * in a way which allows for easy simulation of roles and authentication.
	 *
	 * Note: this will implicitly enable testable HTTP as well.
	 *
	 * @var boolean
	 * @api
	 */
	protected $testableSecurityEnabled = FALSE;

	/**
	 * If enabled, this test case will automatically run the compile() method on
	 * the Persistence Manager before running a test.
	 *
	 * @var boolean
	 * @api
	 * @todo Check if the remaining behavior related to persistence should also be covered by this setting
	 */
	static protected $testablePersistenceEnabled = FALSE;

	/**
	 * Contains a virtual, preinitialized browser
	 *
	 * @var \TYPO3\Flow\Http\Client\Browser
	 * @api
	 */
	protected $browser;

	/**
	 * Contains the router instance used in the browser's request engine
	 *
	 * @var \TYPO3\Flow\Mvc\Routing\Router
	 * @api
	 */
	protected $router;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface
	 */
	protected $privilegeManager;

	/**
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * @var \TYPO3\Flow\Security\Authentication\Provider\TestingProvider
	 */
	protected $testingProvider;

	/**
	 * Initialize Flow
	 *
	 * @return void
	 */
	static public function setUpBeforeClass() {
		self::$bootstrap = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Core\Bootstrap');
	}

	/**
	 * Sets up test requirements depending on the enabled tests.
	 *
	 * If you override this method, don't forget to call parent::setUp() in your
	 * own implementation.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->objectManager = self::$bootstrap->getObjectManager();

		$this->cleanupPersistentResourcesDirectory();

		$session = $this->objectManager->get('TYPO3\Flow\Session\SessionInterface');
		if ($session->isStarted()) {
			$session->destroy(sprintf('assure that session is fresh, in setUp() method of functional test %s.', get_class($this) . '::' . $this->getName()));
		}

		if ($this->testableSecurityEnabled === TRUE || static::$testablePersistenceEnabled === TRUE) {
			if (is_callable(array(self::$bootstrap->getObjectManager()->get('TYPO3\Flow\Persistence\PersistenceManagerInterface'), 'compile'))) {
				$result = self::$bootstrap->getObjectManager()->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->compile();
				if ($result === FALSE) {
					self::markTestSkipped('Test skipped because setting up the persistence failed.');
				}
			}
			$this->persistenceManager = $this->objectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		} else {
			$privilegeManager = $this->objectManager->get('TYPO3\Flow\Security\Authorization\TestingPrivilegeManager');
			$privilegeManager->setOverrideDecision(TRUE);
		}

		// HTTP must be initialized before Session and Security because they rely
		// on an HTTP request being available via the request handler:
		$this->setupHttp();

		$session = $this->objectManager->get('TYPO3\Flow\Session\SessionInterface');
		if ($session->isStarted()) {
			$session->destroy(sprintf('assure that session is fresh, in setUp() method of functional test %s.', get_class($this) . '::' . $this->getName()));
		}

		if ($this->testableSecurityEnabled === TRUE) {
			$this->setupSecurity();
		}
	}

	/**
	 * Sets up security test requirements
	 *
	 * Security is based on action requests so we need a working route for the TestingProvider.
	 *
	 * @return void
	 */
	protected function setupSecurity() {
		$this->privilegeManager = $this->objectManager->get('TYPO3\Flow\Security\Authorization\TestingPrivilegeManager');
		$this->privilegeManager->setOverrideDecision(NULL);

		$this->policyService = $this->objectManager->get('TYPO3\Flow\Security\Policy\PolicyService');

		$this->authenticationManager = $this->objectManager->get('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager');

		$this->testingProvider = $this->objectManager->get('TYPO3\Flow\Security\Authentication\Provider\TestingProvider');
		$this->testingProvider->setName('TestingProvider');

		$this->registerRoute('functionaltestroute', 'typo3/flow/test', array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Mvc\Fixtures',
			'@controller' => 'Standard',
			'@action' => 'index',
			'@format' => 'html'
		));

		$requestHandler = self::$bootstrap->getActiveRequestHandler();
		$actionRequest = $this->route($requestHandler->getHttpRequest());

		$this->securityContext = $this->objectManager->get('TYPO3\Flow\Security\Context');
		$this->securityContext->clearContext();
		$this->securityContext->setRequest($actionRequest);
	}

	/**
	 * @param Request $httpRequest
	 * @return ActionRequest
	 */
	protected function route(Request $httpRequest) {
		$actionRequest = new ActionRequest($httpRequest);
		$matchResults = $this->router->route($httpRequest);
		if ($matchResults !== NULL) {
			$requestArguments = $actionRequest->getArguments();
			$mergedArguments = Arrays::arrayMergeRecursiveOverrule($requestArguments, $matchResults);
			$actionRequest->setArguments($mergedArguments);
		}
		return $actionRequest;
	}

	/**
	 * Tears down test requirements depending on the enabled tests
	 *
	 * Note: tearDown() is also called if an exception occurred in one of the tests. If the problem is caused by
	 *       some security or persistence related part of Flow, the error might be hard to track because their
	 *       specialized tearDown() methods might cause fatal errors. In those cases just output the original
	 *       exception message by adding an echo($this->statusMessage) as the first line of this method.
	 *
	 * @return void
	 */
	public function tearDown() {
		if ($this->testableSecurityEnabled === TRUE) {
			$this->tearDownSecurity();
		}

		$persistenceManager = self::$bootstrap->getObjectManager()->get('TYPO3\Flow\Persistence\PersistenceManagerInterface');

		// Explicitly call persistAll() so that the "allObjectsPersisted" signal is sent even if persistAll()
		// has not been called during a test. This makes sure that for example certain repositories can clear
		// their internal registry in order to avoid side effects in the following test run.
		// Wrap in try/catch to suppress errors after the actual test is run (e.g. validation)
		try {
			$persistenceManager->persistAll();
		} catch (\Exception $exception) {}

		if (is_callable(array($persistenceManager, 'tearDown'))) {
			$persistenceManager->tearDown();
		}

		self::$bootstrap->getObjectManager()->forgetInstance('TYPO3\Flow\Http\Client\InternalRequestEngine');
		self::$bootstrap->getObjectManager()->forgetInstance('TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect');
		$this->inject(self::$bootstrap->getObjectManager()->get('TYPO3\Flow\Resource\ResourceRepository'), 'addedResources', new \SplObjectStorage());
		$this->inject(self::$bootstrap->getObjectManager()->get('TYPO3\Flow\Resource\ResourceRepository'), 'removedResources', new \SplObjectStorage());
		$this->inject(self::$bootstrap->getObjectManager()->get('TYPO3\Flow\Resource\ResourceTypeConverter'), 'convertedResources', array());

		$this->cleanupPersistentResourcesDirectory();
		$this->emitFunctionalTestTearDown();
	}

	/**
	 * Resets security test requirements
	 *
	 * @return void
	 */
	protected function tearDownSecurity() {
		if ($this->privilegeManager !== NULL) {
			$this->privilegeManager->reset();
		}
		if ($this->policyService !== NULL) {
			$this->policyService->reset();
		}
		if ($this->testingProvider !== NULL) {
			$this->testingProvider->reset();
		}
		if ($this->securityContext !== NULL) {
			$this->securityContext->clearContext();
		}
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($this->authenticationManager, 'isAuthenticated', NULL, TRUE);
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
	 * @deprecated since 1.1
	 */
	protected function sendWebRequest($controllerName, $controllerPackageKey, $controllerActionName, array $arguments = array(), $format = 'html') {
		$this->setupHttp();

		$route = new \TYPO3\Flow\Mvc\Routing\Route();
		$route->setName('sendWebRequest Route');

		$uriPattern = 'test/' . uniqid();
		$route->setUriPattern($uriPattern);
		$route->setDefaults(array(
			'@package' => $controllerPackageKey,
			'@controller' => $controllerName,
			'@action' => $controllerActionName,
			'@format' => $format
		));
		$route->setAppendExceedingArguments(TRUE);
		$this->router->addRoute($route);

		$uri = new \TYPO3\Flow\Http\Uri('http://baseuri/' . $uriPattern);
		$response = $this->browser->request($uri, 'POST', $arguments);

		return $response->getContent();
	}

	/**
	 * Creates a new account, assigns it the given roles and authenticates it.
	 * The created account is returned for further modification, for example for attaching a Party object to it.
	 *
	 * @param array $roleNames A list of roles the new account should have
	 * @return \TYPO3\Flow\Security\Account The created account
	 * @api
	 */
	protected function authenticateRoles(array $roleNames) {
		$account = new \TYPO3\Flow\Security\Account();
		$roles = array();
		foreach ($roleNames as $roleName) {
			$roles[] = $this->policyService->getRole($roleName);
		}
		$account->setRoles($roles);
		$this->authenticateAccount($account);

		return $account;
	}

	/**
	 * Prepares the environment for and conducts an account authentication
	 *
	 * @param \TYPO3\Flow\Security\Account $account
	 * @return void
	 * @api
	 */
	protected function authenticateAccount(\TYPO3\Flow\Security\Account $account) {
		$this->testingProvider->setAuthenticationStatus(\TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
		$this->testingProvider->setAccount($account);

		$this->securityContext->clearContext();

		$requestHandler = self::$bootstrap->getActiveRequestHandler();
		$actionRequest = $this->route($requestHandler->getHttpRequest());
		$this->securityContext->setRequest($actionRequest);
		$this->authenticationManager->authenticate();
	}

	/**
	 * Disables authorization for the current test
	 *
	 * @return void
	 * @api
	 */
	protected function disableAuthorization() {
		$this->privilegeManager->setOverrideDecision(TRUE);
	}

	/**
	 * Adds a route that can be used in the functional tests
	 *
	 * @param string $name Name of the route
	 * @param string $uriPattern The uriPattern property of the route
	 * @param array $defaults An array of defaults declarations
	 * @param boolean $appendExceedingArguments If exceeding arguments may be appended
	 * @param array $httpMethods An array of accepted http methods
	 * @return void
	 * @api
	 */
	protected function registerRoute($name, $uriPattern, array $defaults, $appendExceedingArguments = FALSE, array $httpMethods = NULL) {
		$route = new Route();
		$route->setName($name);
		$route->setUriPattern($uriPattern);
		$route->setDefaults($defaults);
		$route->setAppendExceedingArguments($appendExceedingArguments);
		if ($httpMethods !== NULL) {
			$route->setHttpMethods($httpMethods);
		}
		$this->router->addRoute($route);
	}

	/**
	 * Sets up a virtual browser and web environment for seamless HTTP and MVC
	 * related tests.
	 *
	 * @return void
	 */
	protected function setupHttp() {
		$_GET = array();
		$_POST = array();
		$_COOKIE = array();
		$_FILES = array();
		$_SERVER = array (
			'REDIRECT_FLOW_CONTEXT' => 'Development',
			'REDIRECT_FLOW_REWRITEURLS' => '1',
			'REDIRECT_STATUS' => '200',
			'FLOW_CONTEXT' => 'Testing',
			'FLOW_REWRITEURLS' => '1',
			'HTTP_HOST' => 'localhost',
			'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/534.52.7 (KHTML, like Gecko) Version/5.1.2 Safari/534.52.7',
			'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'HTTP_ACCEPT_LANGUAGE' => 'en-us',
			'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
			'HTTP_CONNECTION' => 'keep-alive',
			'PATH' => '/usr/bin:/bin:/usr/sbin:/sbin',
			'SERVER_SIGNATURE' => '',
			'SERVER_SOFTWARE' => 'Apache/2.2.21 (Unix) mod_ssl/2.2.21 OpenSSL/1.0.0e DAV/2 PHP/5.5.1',
			'SERVER_NAME' => 'localhost',
			'SERVER_ADDR' => '127.0.0.1',
			'SERVER_PORT' => '80',
			'REMOTE_ADDR' => '127.0.0.1',
			'DOCUMENT_ROOT' => '/opt/local/apache2/htdocs/',
			'SERVER_ADMIN' => 'george@localhost',
			'SCRIPT_FILENAME' => '/opt/local/apache2/htdocs/Web/index.php',
			'REMOTE_PORT' => '51439',
			'REDIRECT_QUERY_STRING' => '',
			'REDIRECT_URL' => '',
			'GATEWAY_INTERFACE' => 'CGI/1.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_METHOD' => 'GET',
			'QUERY_STRING' => '',
			'REQUEST_URI' => '',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
			'REQUEST_TIME' => 1326472534,
		);

		$this->browser = new \TYPO3\Flow\Http\Client\Browser();
		$this->browser->setRequestEngine(new \TYPO3\Flow\Http\Client\InternalRequestEngine());
		$this->router = $this->browser->getRequestEngine()->getRouter();

		$requestHandler = self::$bootstrap->getActiveRequestHandler();
		$requestHandler->setHttpRequest(Request::create(new \TYPO3\Flow\Http\Uri('http://localhost/typo3/flow/test')));
		$requestHandler->setHttpResponse(new \TYPO3\Flow\Http\Response());
	}

	/**
	 * Cleans up the directory for storing persistent resources during testing
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function cleanupPersistentResourcesDirectory() {
		$settings = self::$bootstrap->getObjectManager()->get('TYPO3\Flow\Configuration\ConfigurationManager')->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
		$resourcesStoragePath = $settings['TYPO3']['Flow']['resource']['storages']['defaultPersistentResourcesStorage']['storageOptions']['path'];
		if (strpos($resourcesStoragePath, FLOW_PATH_DATA) === FALSE) {
			throw new \Exception(sprintf('The storage path for persistent resources for the Testing context is "%s" but it must point to a directory below "%s". Please check the Flow settings for the Testing context.', $resourcesStoragePath, FLOW_PATH_DATA), 1382018388);
		}
		if (file_exists($resourcesStoragePath)) {
			Files::removeDirectoryRecursively($resourcesStoragePath);
		}
	}

	/**
	 * Signals that the functional test case has been executed
	 *
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitFunctionalTestTearDown() {
		self::$bootstrap->getSignalSlotDispatcher()->dispatch(__CLASS__, 'functionalTestTearDown');
	}
}
