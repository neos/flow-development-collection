<?php
use Behat\Behat\Context\BehatContext;
use Flowpack\Behat\Tests\Behat\FlowContext;
use PHPUnit_Framework_Assert as Assert;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Tests\Features\Bootstrap\SubProcess\SubProcess;
use TYPO3\Flow\Utility\Arrays;

require_once(__DIR__ . '/../../../../../../Application/Flowpack.Behat/Tests/Behat/FlowContext.php');

/**
 * Features context
 */
class FeatureContext extends BehatContext
{
    protected static $testingPolicyPathAndFilename;

    /**
     * @var boolean
     */
    protected $isolated = false;

    /**
     * @var SubProcess
     */
    protected $subProcess;

    protected $securityInitialized = false;

    /**
     * @var string
     */
    protected $behatTestHelperObjectName = 'TYPO3\Flow\Tests\Functional\Command\BehatTestHelper';

    /**
     * Initializes the context
     *
     * @param array $parameters Context parameters (configured through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->useContext('flow', new FlowContext($parameters));
        $flowContext = $this->getSubcontext('flow');
        $this->objectManager = $flowContext->getObjectManager();
        $this->environment = $this->objectManager->get('TYPO3\Flow\Utility\Environment');
    }

    /**
     * @AfterFeature
     * @BeforeFeature
     */
    public static function cleanUpSecurity()
    {
        if (file_exists(self::$testingPolicyPathAndFilename)) {
            unlink(self::$testingPolicyPathAndFilename);
        }
    }

    /**
     * @BeforeScenario @Isolated
     * @return void
     */
    public function setIsolatedFlag()
    {
        $this->isolated = true;
    }

    /**
     * @AfterScenario
     */
    public function quitSubProcess()
    {
        if ($this->subProcess !== null) {
            $this->subProcess->quit();
        }
    }

    /**
     * @Given /^I have the following policies:$/
     */
    public function iHaveTheFollowingPolicies($string)
    {
        self::$testingPolicyPathAndFilename = $this->environment->getPathToTemporaryDirectory() . 'Policy.yaml';
        file_put_contents(self::$testingPolicyPathAndFilename, $string->getRaw());

        if ($this->securityInitialized === false) {
            $this->setupSecurity();
            $this->securityInitialized = true;
        }

        $configurationManager = $this->objectManager->get('TYPO3\Flow\Configuration\ConfigurationManager');
        $configurations = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($configurationManager, 'configurations', true);
        unset($configurations[\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_POLICY]);
        \TYPO3\Flow\Reflection\ObjectAccess::setProperty($configurationManager, 'configurations', $configurations, true);

        $policyService = $this->objectManager->get('TYPO3\Flow\Security\Policy\PolicyService');
        \TYPO3\Flow\Reflection\ObjectAccess::setProperty($policyService, 'initialized', false, true);
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
        $this->accessDecisionManager = $this->objectManager->get('TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface');
        $this->accessDecisionManager->setOverrideDecision(null);

        $this->policyService = $this->objectManager->get('TYPO3\Flow\Security\Policy\PolicyService');

        $this->authenticationManager = $this->objectManager->get('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager');

        $this->testingProvider = $this->objectManager->get('TYPO3\Flow\Security\Authentication\Provider\TestingProvider');
        $this->testingProvider->setName('TestingProvider');

        $this->securityContext = $this->objectManager->get('TYPO3\Flow\Security\Context');
        $this->securityContext->clearContext();
        $httpRequest = Request::createFromEnvironment();
        $this->mockActionRequest = new ActionRequest($httpRequest);
        $this->mockActionRequest->setControllerObjectName('TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\AuthenticationController');
        $this->securityContext->setRequest($this->mockActionRequest);
    }

    /**
     * @Given /^I am not authenticated$/
     */
    public function iAmNotAuthenticated()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            if ($this->securityInitialized === false) {
                $this->setupSecurity();
                $this->securityInitialized = true;
            }
        }
    }

    /**
     * @param $stepMethodName string
     * @param $encodedStepArguments string
     */
    protected function callStepInSubProcess($stepMethodName, $encodedStepArguments = '', $withoutSecurityChecks = false)
    {
        if (strpos($stepMethodName, '::') !== 0) {
            $stepMethodName = substr($stepMethodName, strpos($stepMethodName, '::') + 2);
        }
        $withoutSecurityChecks = ($withoutSecurityChecks === true ? '--without-security-checks ' : '');
        $subProcessCommand = sprintf('typo3.flow.tests.functional:behathelper:callbehatstep %s%s %s%s', $withoutSecurityChecks, escapeshellarg($this->behatTestHelperObjectName), $stepMethodName, $encodedStepArguments);

        $subProcessResponse = $this->getSubProcess()->execute($subProcessCommand);

        Assert::assertStringStartsWith('SUCCESS:', $subProcessResponse, 'We called "' . $subProcessCommand . '" and got: ' . $subProcessResponse);
    }

    /**
     * @return SubProcess
     */
    protected function getSubProcess()
    {
        if ($this->subProcess === null) {
            /** @var CacheManager $cacheManager */
            $cacheManager = $this->objectManager->get('TYPO3\Flow\Cache\CacheManager');
            if ($cacheManager->hasCache('Flow_Security_Policy_Privilege_Method')) {
                $cacheManager->getCache('Flow_Security_Policy_Privilege_Method')->flush();
            }

            $objectConfigurationCache = $cacheManager->getCache('Flow_Object_Configuration');
            $objectConfigurationCache->remove('allAspectClassesUpToDate');
            $objectConfigurationCache->remove('allCompiledCodeUpToDate');
            $cacheManager->getCache('Flow_Object_Classes')->flush();

            $this->subProcess = new SubProcess($this->objectManager->getContext());
        }

        return $this->subProcess;
    }

    /**
     * @Given /^I am authenticated with role "([^"]*)"$/
     */
    public function iAmAuthenticatedWithRole($roleIdentifier)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($roleIdentifier)));
        } else {
            if ($this->securityInitialized === false) {
                $this->setupSecurity();
                $this->securityInitialized = true;
            }
            $this->authenticateRoles(Arrays::trimExplode(',', $roleIdentifier));
        }
    }

    /**
     * Creates a new account, assigns it the given roles and authenticates it.
     * The created account is returned for further modification, for example for attaching a Party object to it.
     *
     * @param array $roleNames A list of roles the new account should have
     * @return Account The created account
     */
    protected function authenticateRoles(array $roleNames)
    {
        // FIXME this is currently needed in order to correctly import the roles. Otherwise RepositoryInterface::isConnected() returns FALSE and importing is skipped in PolicyService::initializeRolesFromPolicy()
        $this->objectManager->get('TYPO3\Flow\Security\AccountRepository')->countAll();

        $account = new Account();
        $account->setAccountIdentifier('TestAccount');
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
     * @param Account $account
     * @return void
     */
    protected function authenticateAccount(Account $account)
    {
        $this->testingProvider->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
        $this->testingProvider->setAccount($account);

        $this->securityContext->clearContext();

        /** @var RequestHandler $requestHandler */
        $this->securityContext->setRequest($this->mockActionRequest);
        $this->authenticationManager->authenticate();
    }

    /**
     * @Then /^I can (not )?call the method "([^"]*)" of class "([^"]*)"(?: with arguments "([^"]*)")?$/
     */
    public function iCanCallTheMethodOfClassWithArguments($not, $methodName, $className, $arguments = '')
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s %s %s', 'string', escapeshellarg(trim($not)), 'string', escapeshellarg($methodName), 'string', escapeshellarg($className), 'string', escapeshellarg($arguments)));
        } else {
            if ($this->securityInitialized === false) {
                $this->setupSecurity();
                $this->securityInitialized = true;
            }
            $instance = $this->objectManager->get($className);

            try {
                $result = call_user_func_array(array($instance, $methodName), Arrays::trimExplode(',', $arguments));
                if ($not === 'not') {
                    Assert::fail('Method should not be callable');
                }

                return $result;
            } catch (\TYPO3\Flow\Security\Exception\AccessDeniedException $exception) {
                if ($not !== 'not') {
                    throw $exception;
                }
            }
        }
    }
}
