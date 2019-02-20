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
 * Features context
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
