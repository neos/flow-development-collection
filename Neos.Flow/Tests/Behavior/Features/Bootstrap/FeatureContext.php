<?php

use Behat\Behat\Context\Context;
use Neos\Behat\FlowBootstrapTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use Neos\Flow\Tests\Functional\Command\BehatTestHelper;

require_once(__DIR__ . '/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/SecurityOperationsTrait.php');

/**
 * See {@see IsolatedBehatStepsTrait} documentation for a detailed explanation of Flow's isolated behat tests.
 *
 * @deprecated todo the policy features depending on this handcrafted isolated behat test infrastructure will be refactored and this infrastructure removed.
 * @internal only allowed to be used internally for Neos.Flow behavioral tests!
 */
class FeatureContext implements Context
{
    use FlowBootstrapTrait;
    use IsolatedBehatStepsTrait;
    use SecurityOperationsTrait;

    /**
     * @var string
     */
    protected $behatTestHelperObjectName = BehatTestHelper::class;

    public function __construct()
    {
        self::bootstrapFlow();
        $this->setupSecurity();
    }
}
