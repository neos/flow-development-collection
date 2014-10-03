<?php
namespace TYPO3\Flow\Tests\Functional\Command;

require_once(FLOW_PATH_PACKAGES . '/Framework/TYPO3.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');
require_once(FLOW_PATH_PACKAGES . '/Framework/TYPO3.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(FLOW_PATH_PACKAGES . '/Application/TYPO3.TYPO3CR/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');


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
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface;
use TYPO3\Flow\Security\Authentication\Provider\TestingProvider;
use TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Utility\Environment;

/**
 * A command controller used to execute behat steps in an isolated process.
 * Note: This command controller will only be loaded in Testing context!
 *
 * @see IsolatedBehatStepsTrait
 *
 * @Flow\Scope("singleton")
 */
class BehatHelperCommandController extends CommandController {

	use \IsolatedBehatStepsTrait;

	use \SecurityOperationsTrait;

	use \NodeOperationsTrait;

	/**
	 * @var Bootstrap
	 */
	protected static $bootstrap;

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var Environment
	 */
	protected $environment;

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
	 * @var PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @return void
	 */
	public function initializeObject() {
		self::$bootstrap = Bootstrap::$staticObjectManager->get('TYPO3\Flow\Core\Bootstrap');
		$this->objectManager = self::$bootstrap->getObjectManager();
		$this->propertyMapper = $this->objectManager->get('TYPO3\Flow\Property\PropertyMapper');
		$this->environment = $this->objectManager->get('TYPO3\Flow\Utility\Environment');
		$this->securityContext = $this->objectManager->get('TYPO3\Flow\Security\Context');
		$this->isolated = FALSE;
	}

	/**
	 * @Flow\Internal
	 * @param string $methodName
	 * @param boolean $withoutSecurityChecks
	 */
	public function callBehatStepCommand($methodName, $withoutSecurityChecks = FALSE) {
		$rawMethodArguments = $this->request->getExceedingArguments();
		$mappedArguments = array();
		for ($i = 0; $i < count($rawMethodArguments); $i+=2) {
			$mappedArguments[] = $this->propertyMapper->convert($rawMethodArguments[$i+1], $rawMethodArguments[$i]);
		}

		$result = NULL;
		try {
			if ($withoutSecurityChecks === TRUE) {
				$that = $this;
				$this->securityContext->withoutAuthorizationChecks(function() use ($that, $methodName, $mappedArguments, &$result) {
					$result = call_user_func_array(array($this, $methodName), $mappedArguments);
				});
			} else {
				$result = call_user_func_array(array($this, $methodName), $mappedArguments);
			}
		} catch (\Exception $exception) {
			$this->outputLine('EXCEPTION: %s %s', array($exception->getMessage(), $exception->getTraceAsString()));
			return;
		}
		$this->output('SUCCESS: %s', array($result));
	}

	/**
	 * @return mixed
	 */
	protected function getObjectManager() {
		return $this->objectManager;
	}
}
