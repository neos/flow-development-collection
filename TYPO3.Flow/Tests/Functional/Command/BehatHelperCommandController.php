<?php
namespace TYPO3\Flow\Tests\Functional\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\RequestHandler;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface;
use TYPO3\Flow\Security\Authentication\Provider\TestingProvider;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Utility\Arrays;

/**
 * A collection of useful commands to be called from within behat tests
 * Note: This command controller will only be loaded in Testing context!
 *
 * @Flow\Scope("singleton")
 */
class BehatHelperCommandController extends CommandController {

	/**
	 * @var Bootstrap
	 */
	protected static $bootstrap;

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var ActionRequest
	 */
	protected $mockActionRequest;

	/**
	 * @var AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * @var PolicyService
	 */
	protected $policyService;

	/**
	 * @var AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @var TestingProvider
	 */
	protected $testingProvider;

	/**
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * @return void
	 */
	public function initializeObject() {
		self::$bootstrap = Bootstrap::$staticObjectManager->get('TYPO3\Flow\Core\Bootstrap');
		$this->objectManager = self::$bootstrap->getObjectManager();

		$this->setupSecurity();
	}

	/**
	 * Sets up security test requirements
	 *
	 * Security is based on action requests so we need a working route for the TestingProvider.
	 *
	 * @return void
	 */
	protected function setupSecurity() {
		$this->accessDecisionManager = $this->objectManager->get('TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface');
		$this->accessDecisionManager->setOverrideDecision(NULL);

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

	/**
	 * Authenticate the given roles
	 *
	 * @Flow\Internal
	 * @param string $roles Comma-separated list of identifiers of roles to authenticate
	 * @return void
	 */
	public function authenticateCommand($roles) {
		$this->authenticateRoles(Arrays::trimExplode(',', $roles));

		$this->outputLine('Authenticated roles "' . $roles . '"');
	}


	/**
	 * Call an arbitrary method and return the result - or "EXCEPTION: <exception-code>" if an exception occurred
	 *
	 * @Flow\Internal
	 * @param string $className The fully qualified class name
	 * @param string $methodName The method to call
	 * @param string $parameters Comma-separated list of method arguments
	 */
	public function callMethodCommand($className, $methodName, $parameters = '') {
		$instance = $this->objectManager->get($className);
		try {
			$result = call_user_func_array(array($instance, $methodName), Arrays::trimExplode(',', $parameters));
		} catch (\Exception $exception) {
			$this->outputLine('EXCEPTION: %s', array($exception->getCode()));
			return;
		}
		$this->output('SUCCESS: %s', array($result));
	}


}
