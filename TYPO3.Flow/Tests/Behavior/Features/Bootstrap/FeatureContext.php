<?php
use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\PyStringNode;
use Flowpack\Behat\Tests\Behat\FlowContext;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use TYPO3\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;

require_once(__DIR__ . '/../../../../../../Application/Flowpack.Behat/Tests/Behat/FlowContext.php');
require_once(__DIR__ . '/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/SecurityOperationsTrait.php');

/**
 * Features context
 */
class FeatureContext extends BehatContext {

	use IsolatedBehatStepsTrait;
	use SecurityOperationsTrait;

	/**
	 * @var string
	 */
	protected $behatTestHelperObjectName = 'TYPO3\Flow\Tests\Functional\Command\BehatTestHelper';

	/**
	 * Initializes the context
	 *
	 * @param array $parameters Context parameters (configured through behat.yml)
	 */
	public function __construct(array $parameters) {
		$this->useContext('flow', new FlowContext($parameters));
		$flowContext = $this->getSubcontext('flow');
		$this->objectManager = $flowContext->getObjectManager();
		$this->environment = $this->objectManager->get('TYPO3\Flow\Utility\Environment');
		$this->setupSecurity();
	}
}
