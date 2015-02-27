<?php
namespace TYPO3\Flow\Tests\Behavior\Features\Bootstrap;

use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Utility\Arrays;
use PHPUnit_Framework_Assert as Assert;

/**
 * A trait with shared step definitions for testing compile time security privilege types
 * (e.g. method privileges)
 *
 * Note that this trait requires that the following members are available:
 *
 * - $this->objectManager (TYPO3\Flow\Object\ObjectManagerInterface)
 * - $this->environment (TYPO3\Flow\Utility\Environment)
 *
 * Note: This trait expects the IsolatedBehatStepsTrait to be available!
 */
trait SecurityOperationsTrait {

	protected $securityInitialized = FALSE;

	protected static $testingPolicyPathAndFilename;

	/**
	 * @Given /^I have the following policies:$/
	 */
	public function iHaveTheFollowingPolicies($string) {
		self::$testingPolicyPathAndFilename = $this->environment->getPathToTemporaryDirectory() . 'Policy.yaml';
		file_put_contents(self::$testingPolicyPathAndFilename, $string->getRaw());

		if ($this->securityInitialized === FALSE) {
			$this->setupSecurity();
			$this->securityInitialized = TRUE;
		}

		$configurationManager = $this->objectManager->get('TYPO3\Flow\Configuration\ConfigurationManager');
		$configurations = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($configurationManager, 'configurations', TRUE);
		unset($configurations[\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_POLICY]);
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($configurationManager, 'configurations', $configurations, TRUE);

		$policyService = $this->objectManager->get('TYPO3\Flow\Security\Policy\PolicyService');
		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($policyService, 'initialized', FALSE, TRUE);
	}

	/**
	 * @AfterFeature
	 * @BeforeFeature
	 */
	public static function cleanUpSecurity() {
		if (file_exists(self::$testingPolicyPathAndFilename)) {
			unlink(self::$testingPolicyPathAndFilename);
		}
	}

	/**
	 * @Given /^I am not authenticated$/
	 */
	public function iAmNotAuthenticated() {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__);
		} else {
			if ($this->securityInitialized === FALSE) {
				$this->setupSecurity();
				$this->securityInitialized = TRUE;
			}
		}
	}

	/**
	 * @Given /^I am authenticated with role "([^"]*)"$/
	 */
	public function iAmAuthenticatedWithRole($roleIdentifier) {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s', 'string', escapeshellarg($roleIdentifier)));
		} else {
			if ($this->securityInitialized === FALSE) {
				$this->setupSecurity();
				$this->securityInitialized = TRUE;
			}
			$this->authenticateRoles(Arrays::trimExplode(',', $roleIdentifier));
		}
	}

	/**
	 * @Then /^I can (not )?call the method "([^"]*)" of class "([^"]*)"(?: with arguments "([^"]*)")?$/
	 */
	public function iCanCallTheMethodOfClassWithArguments($not, $methodName, $className, $arguments = '') {
		if ($this->isolated === TRUE) {
			$this->callStepInSubProcess(__METHOD__, sprintf(' %s %s %s %s %s %s %s %s', 'string', escapeshellarg(trim($not)), 'string', escapeshellarg($methodName), 'string', escapeshellarg($className), 'string', escapeshellarg($arguments)));
		} else {
			if ($this->securityInitialized === FALSE) {
				$this->setupSecurity();
				$this->securityInitialized = TRUE;
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

	/**
	 * Sets up security test requirements
	 *
	 * Security is based on action requests so we need a working route for the TestingProvider.
	 *
	 * @return void
	 */
	protected function setupSecurity() {
		$this->privilegeManager = $this->objectManager->get('TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface');
		$this->privilegeManager->setOverrideDecision(NULL);

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
	 * Creates a new account, assigns it the given roles and authenticates it.
	 * The created account is returned for further modification, for example for attaching a Party object to it.
	 *
	 * @param array $roleNames A list of roles the new account should have
	 * @return Account The created account
	 */
	protected function authenticateRoles(array $roleNames) {
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
	protected function authenticateAccount(Account $account) {
		$this->testingProvider->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);
		$this->testingProvider->setAccount($account);

		$this->securityContext->clearContext();

		/** @var RequestHandler $requestHandler */
		$this->securityContext->setRequest($this->mockActionRequest);
		$this->authenticationManager->authenticate();
	}
}
