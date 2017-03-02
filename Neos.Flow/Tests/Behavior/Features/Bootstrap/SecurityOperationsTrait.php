<?php
namespace Neos\Flow\Tests\Behavior\Features\Bootstrap;

use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\RequestHandler;
use Neos\Flow\Mvc\ActionRequest;
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
use PHPUnit_Framework_Assert as Assert;

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
 */
trait SecurityOperationsTrait
{
    protected $securityInitialized = false;

    protected static $testingPolicyPathAndFilename;

    /**
     * WARNING: If using this step definition, IT MUST RUN AS ABSOLUTELY FIRST STEP IN A SCENARIO!
     *
     * @Given /^I have the following policies:$/
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
     * @Then /^I can (not )?call the method "([^"]*)" of class "([^"]*)"(?: with arguments "([^"]*)")?$/
     */
    public function iCanCallTheMethodOfClassWithArguments($not, $methodName, $className, $arguments = '')
    {
        if ($this->isolated === true) {
            $this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s %s %s', 'string', escapeshellarg(trim($not)), 'string', escapeshellarg($methodName), 'string', escapeshellarg($className), 'string', escapeshellarg($arguments)));
        } else {
            $this->setupSecurity();
            $instance = $this->objectManager->get($className);

            try {
                $result = call_user_func_array([$instance, $methodName], Arrays::trimExplode(',', $arguments));
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
     */
    protected function setupSecurity()
    {
        if ($this->securityInitialized === true) {
            return;
        }
        $this->privilegeManager = $this->objectManager->get(PrivilegeManagerInterface::class);
        $this->privilegeManager->setOverrideDecision(null);

        $this->policyService = $this->objectManager->get(PolicyService::class);

        $this->authenticationManager = $this->objectManager->get(AuthenticationProviderManager::class);

        $this->testingProvider = $this->objectManager->get(TestingProvider::class);
        $this->testingProvider->setName('TestingProvider');

        $this->securityContext = $this->objectManager->get(Security\Context::class);
        $this->securityContext->clearContext();
        $httpRequest = Request::createFromEnvironment();
        $this->mockActionRequest = new ActionRequest($httpRequest);
        $this->mockActionRequest->setControllerObjectName(AuthenticationController::class);
        $this->securityContext->setRequest($this->mockActionRequest);

        $this->securityInitialized = true;
    }

    /**
     * Creates a new account, assigns it the given roles and authenticates it.
     * The created account is returned for further modification, for example for attaching a Party object to it.
     *
     * @param array $roleNames A list of roles the new account should have
     * @return Security\Accountt The created account
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
