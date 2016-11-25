<?php
namespace Neos\Flow\Tests;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Request;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Utility\Arrays;
use Neos\Utility\Files;

/**
 * A base test case for functional tests
 *
 * Subclass this base class if you want to take advantage of the framework
 * capabilities, for example are in need of the object manager.
 *
 * @api
 */
abstract class FunctionalTestCase extends \Neos\Flow\Tests\BaseTestCase
{
    /**
     * A functional instance of the Object Manager, for use in concrete test cases.
     *
     * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
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
    protected $testableSecurityEnabled = false;

    /**
     * If enabled, this test case will automatically run the compile() method on
     * the Persistence Manager before running a test.
     *
     * @var boolean
     * @api
     * @todo Check if the remaining behavior related to persistence should also be covered by this setting
     */
    protected static $testablePersistenceEnabled = false;

    /**
     * Contains a virtual, preinitialized browser
     *
     * @var \Neos\Flow\Http\Client\Browser
     * @api
     */
    protected $browser;

    /**
     * Contains the router instance used in the browser's request engine
     *
     * @var \Neos\Flow\Mvc\Routing\Router
     * @api
     */
    protected $router;

    /**
     * @var \Neos\Flow\Security\Context
     */
    protected $securityContext;

    /**
     * @var \Neos\Flow\Security\Authentication\AuthenticationManagerInterface
     */
    protected $authenticationManager;

    /**
     * @var \Neos\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var \Neos\Flow\Security\Authorization\PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @var \Neos\Flow\Security\Policy\PolicyService
     */
    protected $policyService;

    /**
     * @var \Neos\Flow\Security\Authentication\Provider\TestingProvider
     */
    protected $testingProvider;

    /**
     * Initialize Flow
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$bootstrap = \Neos\Flow\Core\Bootstrap::$staticObjectManager->get(\Neos\Flow\Core\Bootstrap::class);
        self::setupSuperGlobals();
    }

    /**
     * Sets up test requirements depending on the enabled tests.
     *
     * If you override this method, don't forget to call parent::setUp() in your
     * own implementation.
     *
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = self::$bootstrap->getObjectManager();

        $this->cleanupPersistentResourcesDirectory();
        self::$bootstrap->getObjectManager()->forgetInstance(\Neos\Flow\ResourceManagement\ResourceManager::class);
        $session = $this->objectManager->get(\Neos\Flow\Session\SessionInterface::class);
        if ($session->isStarted()) {
            $session->destroy(sprintf('assure that session is fresh, in setUp() method of functional test %s.', get_class($this) . '::' . $this->getName()));
        }

        if ($this->testableSecurityEnabled === true || static::$testablePersistenceEnabled === true) {
            if (is_callable(array(self::$bootstrap->getObjectManager()->get(\Neos\Flow\Persistence\PersistenceManagerInterface::class), 'compile'))) {
                $result = self::$bootstrap->getObjectManager()->get(\Neos\Flow\Persistence\PersistenceManagerInterface::class)->compile();
                if ($result === false) {
                    self::markTestSkipped('Test skipped because setting up the persistence failed.');
                }
            }
            $this->persistenceManager = $this->objectManager->get(\Neos\Flow\Persistence\PersistenceManagerInterface::class);
        } else {
            $privilegeManager = $this->objectManager->get(\Neos\Flow\Security\Authorization\TestingPrivilegeManager::class);
            $privilegeManager->setOverrideDecision(true);
        }

        // HTTP must be initialized before Session and Security because they rely
        // on an HTTP request being available via the request handler:
        $this->setupHttp();

        $session = $this->objectManager->get(\Neos\Flow\Session\SessionInterface::class);
        if ($session->isStarted()) {
            $session->destroy(sprintf('assure that session is fresh, in setUp() method of functional test %s.', get_class($this) . '::' . $this->getName()));
        }

        $this->setupSecurity();
    }

    /**
     * Sets up security test requirements
     *
     * Security is based on action requests so we need a working route for the TestingProvider.
     *
     * @return void
     */
    protected function setupSecurity()
    {
        $this->securityContext = $this->objectManager->get(\Neos\Flow\Security\Context::class);
        if ($this->testableSecurityEnabled) {
            $this->privilegeManager = $this->objectManager->get(\Neos\Flow\Security\Authorization\TestingPrivilegeManager::class);
            $this->privilegeManager->setOverrideDecision(null);

            $this->policyService = $this->objectManager->get(\Neos\Flow\Security\Policy\PolicyService::class);

            $this->authenticationManager = $this->objectManager->get(\Neos\Flow\Security\Authentication\AuthenticationProviderManager::class);

            $this->testingProvider = $this->objectManager->get(\Neos\Flow\Security\Authentication\Provider\TestingProvider::class);
            $this->testingProvider->setName('TestingProvider');

            $this->registerRoute('functionaltestroute', 'typo3/flow/test', array(
                '@package' => 'Neos.Flow',
                '@subpackage' => 'Tests\Functional\Mvc\Fixtures',
                '@controller' => 'Standard',
                '@action' => 'index',
                '@format' => 'html'
            ));

            $requestHandler = self::$bootstrap->getActiveRequestHandler();
            $actionRequest = $this->route($requestHandler->getHttpRequest());

            $this->securityContext->clearContext();
            $this->securityContext->setRequest($actionRequest);
        } else {
            \Neos\Utility\ObjectAccess::setProperty($this->securityContext, 'authorizationChecksDisabled', true, true);
        }
    }

    /**
     * @param Request $httpRequest
     * @return ActionRequest
     */
    protected function route(Request $httpRequest)
    {
        $actionRequest = new ActionRequest($httpRequest);
        $matchResults = $this->router->route($httpRequest);
        if ($matchResults !== null) {
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
    public function tearDown()
    {
        $this->tearDownSecurity();

        $persistenceManager = self::$bootstrap->getObjectManager()->get(\Neos\Flow\Persistence\PersistenceManagerInterface::class);

        // Explicitly call persistAll() so that the "allObjectsPersisted" signal is sent even if persistAll()
        // has not been called during a test. This makes sure that for example certain repositories can clear
        // their internal registry in order to avoid side effects in the following test run.
        // Wrap in try/catch to suppress errors after the actual test is run (e.g. validation)
        try {
            $persistenceManager->persistAll();
        } catch (\Exception $exception) {
        }

        if (is_callable(array($persistenceManager, 'tearDown'))) {
            $persistenceManager->tearDown();
        }

        self::$bootstrap->getObjectManager()->forgetInstance(\Neos\Flow\Http\Client\InternalRequestEngine::class);
        self::$bootstrap->getObjectManager()->forgetInstance(\Neos\Flow\Persistence\Aspect\PersistenceMagicAspect::class);
        $this->inject(self::$bootstrap->getObjectManager()->get(\Neos\Flow\ResourceManagement\ResourceRepository::class), 'addedResources', new \SplObjectStorage());
        $this->inject(self::$bootstrap->getObjectManager()->get(\Neos\Flow\ResourceManagement\ResourceRepository::class), 'removedResources', new \SplObjectStorage());
        $this->inject(self::$bootstrap->getObjectManager()->get(\Neos\Flow\ResourceManagement\ResourceTypeConverter::class), 'convertedResources', array());

        $this->cleanupPersistentResourcesDirectory();
        $this->emitFunctionalTestTearDown();
    }

    /**
     * Resets security test requirements
     *
     * @return void
     */
    protected function tearDownSecurity()
    {
        if ($this->privilegeManager !== null) {
            $this->privilegeManager->reset();
        }
        if ($this->policyService !== null) {
            $this->policyService->reset();
        }
        if ($this->testingProvider !== null) {
            $this->testingProvider->reset();
        }
        if ($this->securityContext !== null) {
            $this->securityContext->clearContext();
        }
        if ($this->authenticationManager !== null) {
            \Neos\Utility\ObjectAccess::setProperty($this->authenticationManager, 'isAuthenticated', null, true);
        }
    }

    /**
     * Creates a new account, assigns it the given roles and authenticates it.
     * The created account is returned for further modification, for example for attaching a Party object to it.
     *
     * @param array $roleNames A list of roles the new account should have
     * @return \Neos\Flow\Security\Account The created account
     * @api
     */
    protected function authenticateRoles(array $roleNames)
    {
        $account = new \Neos\Flow\Security\Account();
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
     * @param \Neos\Flow\Security\Account $account
     * @return void
     * @api
     */
    protected function authenticateAccount(\Neos\Flow\Security\Account $account)
    {
        $this->testingProvider->setAuthenticationStatus(\Neos\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
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
    protected function disableAuthorization()
    {
        $this->privilegeManager->setOverrideDecision(true);
    }

    /**
     * Adds a route that can be used in the functional tests
     *
     * @param string $name Name of the route
     * @param string $uriPattern The uriPattern property of the route
     * @param array $defaults An array of defaults declarations
     * @param boolean $appendExceedingArguments If exceeding arguments may be appended
     * @param array $httpMethods An array of accepted http methods
     * @return Route
     * @api
     */
    protected function registerRoute($name, $uriPattern, array $defaults, $appendExceedingArguments = false, array $httpMethods = null)
    {
        $route = new Route();
        $route->setName($name);
        $route->setUriPattern($uriPattern);
        $route->setDefaults($defaults);
        $route->setAppendExceedingArguments($appendExceedingArguments);
        if ($httpMethods !== null) {
            $route->setHttpMethods($httpMethods);
        }
        $this->router->addRoute($route);
        return $route;
    }

    /**
     * Setup super global PHP variables mocking a standard http request.
     *
     * @return void
     */
    protected static function setupSuperGlobals()
    {
        $_GET = array();
        $_POST = array();
        $_COOKIE = array();
        $_FILES = array();
        $_SERVER = array(
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
            'SERVER_SOFTWARE' => 'Apache/2.2.21 (Unix) mod_ssl/2.2.21 OpenSSL/1.0.0e DAV/2 PHP/7.0.12',
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
    }

    /**
     * Sets up a virtual browser and web environment for seamless HTTP and MVC
     * related tests.
     *
     * @return void
     */
    protected function setupHttp()
    {
        $this->browser = new \Neos\Flow\Http\Client\Browser();
        $this->browser->setRequestEngine(new \Neos\Flow\Http\Client\InternalRequestEngine());
        $this->router = $this->browser->getRequestEngine()->getRouter();
        $this->router->setRoutesConfiguration(null);

        $requestHandler = self::$bootstrap->getActiveRequestHandler();
        $request = Request::create(new \Neos\Flow\Http\Uri('http://localhost/typo3/flow/test'));
        $componentContext = new ComponentContext($request, new \Neos\Flow\Http\Response());
        $requestHandler->setComponentContext($componentContext);
    }

    /**
     * Cleans up the directory for storing persistent resources during testing
     *
     * @return void
     * @throws \Exception
     */
    protected function cleanupPersistentResourcesDirectory()
    {
        $settings = self::$bootstrap->getObjectManager()->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
        $resourcesStoragePath = $settings['Neos']['Flow']['resource']['storages']['defaultPersistentResourcesStorage']['storageOptions']['path'];
        if (strpos($resourcesStoragePath, FLOW_PATH_DATA) === false) {
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
    protected function emitFunctionalTestTearDown()
    {
        self::$bootstrap->getSignalSlotDispatcher()->dispatch(__CLASS__, 'functionalTestTearDown');
    }
}
