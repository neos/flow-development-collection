<?php
use Behat\Behat\Context\Context;
use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use Neos\Flow\Tests\Functional\Command\BehatTestHelper;
use Neos\Flow\Utility\Environment;

require_once(__DIR__ . '/../../../../../../Application/Neos.Behat/Tests/Behat/FlowContextTrait.php');
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
    use FlowContextTrait;
    use IsolatedBehatStepsTrait;
    use SecurityOperationsTrait;

    /**
     * @var string
     */
    protected $behatTestHelperObjectName = BehatTestHelper::class;

    /**
     * @var Environment
     */
    protected $environment;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();

        $this->environment = $this->objectManager->get(Environment::class);
        $this->setupSecurity();
    }
}
