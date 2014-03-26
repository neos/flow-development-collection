<?php
use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\PyStringNode;
use Flowpack\Behat\Tests\Behat\FlowContext;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Tests\Features\Bootstrap\SubProcess\SubProcess;
use TYPO3\Flow\Utility\Environment;

require_once(__DIR__ . '/../../../../../../Application/Flowpack.Behat/Tests/Behat/FlowContext.php');
require_once(__DIR__ . '/SubProcess/SubProcess.php');

/**
 * Features context
 */
class FeatureContext extends BehatContext {

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var Environment
	 */
	protected $environment;

	/**
	 * @var SubProcess
	 */
	protected $subProcess;

	/**
	 * Initializes the context
	 *
	 * @param array $parameters Context parameters (configured through behat.yml)
	 */
	public function __construct(array $parameters) {
		$this->useContext('flow', new FlowContext($parameters));
		$flowContext = $this->getFlowSubContext();
		$this->objectManager = $flowContext->getObjectManager();
		$this->environment = $this->objectManager->get('TYPO3\Flow\Utility\Environment');
	}

	/**
	 * @return SubProcess
	 */
	protected function getSubProcess() {
		if ($this->subProcess === NULL) {
			/** @var CacheManager $cacheManager */
			$cacheManager = $this->objectManager->get('TYPO3\Flow\Cache\CacheManager');
			if ($cacheManager->hasCache('Flow_Security_Policy_Privilege_Method')) {
				$cacheManager->getCache('Flow_Security_Policy_Privilege_Method')->flush();
			}

			$objectConfigurationCache = $cacheManager->getCache('Flow_Object_Configuration');
			$objectConfigurationCache->remove('allAspectClassesUpToDate');
			$objectConfigurationCache->remove('allCompiledCodeUpToDate');
			$cacheManager->getCache('Flow_Object_Classes')->flush();

			$this->subProcess = new SubProcess($this->objectManager->getContext());
		}
		return $this->subProcess;
	}

	/**
	 * @AfterScenario
	 */
	public function quitSubProcess() {
		if ($this->subProcess !== NULL) {
			$this->subProcess->quit();
		}
	}

	/**
	 * @return FlowContext
	 */
	protected function getFlowSubContext() {
		return $this->getSubcontext('flow');
	}

	/**
	 * @Given /^I have the following policies:$/
	 */
	public function iHaveTheFollowingPolicies(PyStringNode $string) {
		$testingPolicyPathAndFilename = $this->environment->getPathToTemporaryDirectory() . 'Policy.yaml';
		file_put_contents($testingPolicyPathAndFilename, $string->getRaw());
	}

	/**
	 * @Given /^I am not authenticated$/
	 */
	public function iAmNotAuthenticated() {
		// Do nothing, every scenario has a new session
	}

	/**
	 * @Given /^I am authenticated with role "([^"]*)"$/
	 */
	public function iAmAuthenticatedWith($roleIdentifier) {
		$subProcessResponse = $this->getSubProcess()->execute('typo3.flow.tests.functional:behathelper:authenticate --roles ' . $roleIdentifier);
		\PHPUnit_Framework_Assert::assertStringStartsWith('Authenticated roles', $subProcessResponse, 'Expected "Authenticated roles..." output got "' . $subProcessResponse . '"');
	}

	/**
	 * @Then /^I can (not )?call the method "([^"]*)" of class "([^"]*)"$/
	 */
	public function iCanCallTheMethodOfClass($not, $methodName, $className) {
		$subProcessResponse = $this->getSubProcess()->execute('typo3.flow.tests.functional:behathelper:callmethod --className ' . $className . ' --methodName ' . $methodName);
		if ($not === '') {
			\PHPUnit_Framework_Assert::assertStringStartsWith('SUCCESS:', $subProcessResponse, 'Expected "SUCCESS: ..." output got "' . $subProcessResponse . '"');
		} else {
			\PHPUnit_Framework_Assert::assertSame('EXCEPTION: 1222268609', $subProcessResponse, 'Expected AccessDeniedException (#1222268609), got "' . $subProcessResponse . '"');
		}
	}
}
