<?php
use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\PyStringNode;
use Neos\Behat\Tests\Behat\FlowContext;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use Neos\Flow\Tests\Functional\Command\BehatTestHelper;
use Neos\Flow\Utility\Environment;

require_once(__DIR__ . '/../../../../../../Application/Neos.Behat/Tests/Behat/FlowContext.php');
require_once(__DIR__ . '/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/SecurityOperationsTrait.php');

/**
 * Features context
 */
class FeatureContext extends BehatContext
{
    use IsolatedBehatStepsTrait;
    use SecurityOperationsTrait;

    /**
     * @var string
     */
    protected $behatTestHelperObjectName = BehatTestHelper::class;

    /**
     * Initializes the context
     *
     * @param array $parameters Context parameters (configured through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->useContext('flow', new FlowContext($parameters));
        $flowContext = $this->getSubcontext('flow');
        $this->objectManager = $flowContext->getObjectManager();
        $this->environment = $this->objectManager->get(Environment::class);
        $this->setupSecurity();
    }
}
