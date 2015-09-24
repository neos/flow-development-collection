<?php
namespace TYPO3\Flow\Tests\Functional\Command;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

require_once(FLOW_PATH_PACKAGES . '/Framework/TYPO3.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(FLOW_PATH_PACKAGES . '/Framework/TYPO3.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');

use TYPO3\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use TYPO3\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface;
use TYPO3\Flow\Security\Authentication\Provider\TestingProvider;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Utility\Environment;

/**
 * A test helper, to include behat step traits, beeing executed by
 * the BehatHelperCommandController.
 *
 * @Flow\Scope("singleton")
 */
class BehatTestHelper
{
    use IsolatedBehatStepsTrait;

    use SecurityOperationsTrait;

    /**
     * @var Bootstrap
     */
    protected static $bootstrap;

    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var Environment
     * @Flow\Inject
     */
    protected $environment;

    /**
     * @var ActionRequest
     */
    protected $mockActionRequest;

    /**
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @var PolicyService
     * @Flow\Inject
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
    public function initializeObject()
    {
        self::$bootstrap = Bootstrap::$staticObjectManager->get(\TYPO3\Flow\Core\Bootstrap::class);
        $this->isolated = false;
    }

    /**
     * @return mixed
     */
    protected function getObjectManager()
    {
        return $this->objectManager;
    }
}
