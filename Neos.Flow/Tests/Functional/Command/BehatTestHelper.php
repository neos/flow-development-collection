<?php
namespace Neos\Flow\Tests\Functional\Command;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(FLOW_PATH_PACKAGES . '/Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(FLOW_PATH_PACKAGES . '/Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use Neos\Flow\Utility\Environment;

/**
 * A test helper, to include behat step traits, beeing executed by
 * the BehatHelperCommandController.
 *
 * @Flow\Scope("singleton")
 */
class BehatTestHelper
{
    use SecurityOperationsTrait;

    protected $isolated = false;

    /** @var Bootstrap */
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
     * @var PolicyService
     * @Flow\Inject
     */
    protected $policyService;

    public function initializeObject(): void
    {
        self::$bootstrap = Bootstrap::$staticObjectManager->get(Bootstrap::class);
    }

    /** @return ObjectManagerInterface */
    protected function getObjectManager()
    {
        return $this->objectManager;
    }
}
