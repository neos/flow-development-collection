<?php
namespace Neos\Flow\Tests\Behavior\Features\Bootstrap;

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Exception;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ObjectManagement\Exception\UnknownObjectException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Utility\Environment;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Security;
use Neos\Flow\Security\Authentication\AuthenticationProviderManager;
use Neos\Flow\Security\Authentication\Provider\TestingProvider;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Tests\Functional\Security\Fixtures\Controller\AuthenticationController;
use Neos\Utility\Arrays;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * A trait with shared step definitions for testing compile time security privilege types
 * (e.g. method privileges)
 *
 * Note that this trait requires that the following members are available:
 *
 * - $this->objectManager (Neos\Flow\ObjectManagement\ObjectManagerInterface)
 * - $this->environment (Neos\Flow\Utility\Environment)
 *
 * Note: This trait expects the IsolatedBehatStepsTrait to be available!
 *
 * Note: Make sure to call $this->setupSecurity() in the constructor of your
 * behat context for these steps to work in your tests!
 *
 * @property Environment environment
 * @property ObjectManagerInterface objectManager
 */
trait SecurityOperationsTrait
{
    protected $securityInitialized = false;

    protected static $testingPolicyPathAndFilename;

    /**
     * @var Security\AccountRepository
     */
    protected $accountRepository;

    /**
     * @var AuthenticationProviderManager
     */
    protected $authenticationManager;

    /**
     * @var Security\Authentication\TokenAndProviderFactoryInterface
     */
    protected $tokenAndProviderFactory;

    /**
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @var Security\Context
     */
    protected $securityContext;

    /**
     * @var TestingProvider
     */
    protected $testingProvider;

    /**
     * WARNING: If using this step definition, IT MUST RUN AS ABSOLUTELY FIRST STEP IN A SCENARIO!
     *
     * @Given /^I have the following policies:$/
     * @throws \Exception
     */
    public function iHaveTheFollowingPolicies($string)
    {
        if ($this->subProcess !== null) {
            // This check ensures that this statement is ran *before* a subprocess is opened; as the Policy.yaml
            // which is set here influences the Proxy Building Process.
            throw new \Exception('Step "I have the following policies:" must run as FIRST step in a scenario, because otherwise the proxy-classes are already built in the wrong manner!');
        }

        self::$testingPolicyPathAndFilename = $this->environment->getPathToTemporaryDirectory() . 'Policy.yaml';
        file_put_contents(self::$testingPolicyPathAndFilename, $string->getRaw());

        $configurationManager = $this->objectManager->get(ConfigurationManager::class);
        $configurations = ObjectAccess::getProperty($configurationManager, 'configurations', true);
        unset($configurations[ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_POLICY]);
        ObjectAccess::setProperty($configurationManager, 'configurations', $configurations, true);

        $policyService = $this->objectManager->get(PolicyService::class);
        ObjectAccess::setProperty($policyService, 'initialized', false, true);
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
     * @Given /^I am not authenticated$/
     * @throws UnknownObjectException
     */
    public function iAmNotAuthenticated()
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__);
        } else {
            $this->setupSecurity();
        }
    }

    /**
     * @Given /^I am authenticated with role "([^"]*)"$/
     * @param $roleIdentifier
     * @throws Security\Exception
     * @throws Security\Exception\AuthenticationRequiredException
     * @throws UnknownObjectException
     */
    public function iAmAuthenticatedWithRole($roleIdentifier)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($roleIdentifier)));
        } else {
            $this->setupSecurity();
            $this->authenticateRoles(Arrays::trimExplode(',', $roleIdentifier));
        }
    }

    /**
     * @Given /^I am authenticated as "([^"]*)" via authentication provider "([^"]*)"$/
     * @param string $accountIdentifier
     * @param string|null $authenticationProviderName
     * @throws Security\Exception
     * @throws Security\Exception\AuthenticationRequiredException
     * @throws UnknownObjectException
     * @throws Exception
     */
    public function iAmAuthenticatedAs(string $accountIdentifier, string $authenticationProviderName)
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s', 'string', escapeshellarg($accountIdentifier), 'string', escapeshellarg($authenticationProviderName)));
        } else {
            $this->setupSecurity();
            $account = $this->accountRepository->findByAccountIdentifierAndAuthenticationProviderName($accountIdentifier, $authenticationProviderName);
            if ($account) {
                $this->authenticateAccount($account);
            } else {
                throw new Exception('Authentication unsuccessful, account "' . $accountIdentifier . ($authenticationProviderName ? '@' . $authenticationProviderName : '') . '" is missing', 1518179642);
            }
        }
    }

    /**
     * @Then /^I can (not )?call the method "([^"]*)" of class "([^"]*)"(?: with arguments "([^"]*)")?$/
     * @throws UnknownObjectException
     * @throws AccessDeniedException
     */
    public function iCanCallTheMethodOfClassWithArguments($not, $methodName, $className, $arguments = '')
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s %s %s', 'string', escapeshellarg(trim($not)), 'string', escapeshellarg($methodName), 'string', escapeshellarg($className), 'string', escapeshellarg($arguments)));
        } else {
            $this->setupSecurity();
            $instance = $this->objectManager->get($className);

            try {
                $result = $instance->$methodName(...Arrays::trimExplode(',', $arguments));
                if ($not === 'not') {
                    Assert::fail('Method should not be callable');
                }
                return $result;
            } catch (AccessDeniedException $exception) {
                if ($not !== 'not') {
                    throw $exception;
                }
            }
        }
    }

    /**
     * Sets up security test requirements
     *
     * Security is based on action requests so we need a working route for the TestingProvider.
     *
     * @return void
     * @throws UnknownObjectException
     */
    protected function setupSecurity()
    {
        if ($this->securityInitialized === true) {
            return;
        }
        $this->privilegeManager = $this->objectManager->get(PrivilegeManagerInterface::class);
        $this->privilegeManager->setOverrideDecision(null);

        $this->policyService = $this->objectManager->get(PolicyService::class);
        $this->accountRepository = $this->objectManager->get(Security\AccountRepository::class);
        $this->authenticationManager = $this->objectManager->get(AuthenticationProviderManager::class);
        $this->tokenAndProviderFactory = $this->objectManager->get(Security\Authentication\TokenAndProviderFactoryInterface::class);

        // Making sure providers and tokens were actually build, so the singleton TestingProvider exists.
        $this->tokenAndProviderFactory->getProviders();

        $this->testingProvider = $this->objectManager->get(TestingProvider::class);
        $this->testingProvider->setName('TestingProvider');

        $this->securityContext = $this->objectManager->get(Security\Context::class);
        $this->securityContext->clearContext();
        $httpRequest = $this->objectManager->get(ServerRequestFactoryInterface::class)->createServerRequest('GET', 'http://localhost/');
        $this->mockActionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $this->mockActionRequest->setControllerObjectName(AuthenticationController::class);
        $this->securityContext->setRequest($this->mockActionRequest);

        $this->securityInitialized = true;
    }

    /**
     * Creates a new account, assigns it the given roles and authenticates it.
     * The created account is returned for further modification, for example for attaching a Party object to it.
     *
     * @param array $roleNames A list of roles the new account should have
     * @return Security\Account The created account
     * @throws Security\Exception
     * @throws Security\Exception\AuthenticationRequiredException
     */
    protected function authenticateRoles(array $roleNames)
    {
        $account = new Security\Account();
        $account->setAccountIdentifier('TestAccount');
        $roles = [];
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
     * @param Security\Account $account
     * @return void
     * @throws Security\Exception
     * @throws Security\Exception\AuthenticationRequiredException
     */
    protected function authenticateAccount(Security\Account $account)
    {
        $this->testingProvider->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
        $this->testingProvider->setAccount($account);

        $this->securityContext->clearContext();

        $this->securityContext->setRequest($this->mockActionRequest);
        $this->authenticationManager->authenticate();
    }
}
