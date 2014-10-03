<?php

require_once(__DIR__ . '/SubProcess/SubProcess.php');

use TYPO3\Flow\Tests\Features\Bootstrap\SubProcess\SubProcess;
use TYPO3\Flow\Cache\CacheManager;
use PHPUnit_Framework_Assert as Assert;

/**
 * Class IsolatedBehatStepsTrait
 */
trait IsolatedBehatStepsTrait {

	/**
	 * @var boolean
	 */
	protected $isolated = FALSE;

	/**
	 * @var SubProcess
	 */
	protected $subProcess;

	/**
	 * @BeforeScenario @Isolated
	 * @return void
	 */
	public function setIsolatedFlag() {
		$this->isolated = TRUE;
	}

	/**
	 * @return SubProcess
	 */
	protected function getSubProcess() {
		if ($this->subProcess === NULL) {
			/** @var CacheManager $cacheManager */
			$cacheManager = $this->objectManager->get('TYPO3\Flow\Cache\CacheManager');
			if ($cacheManager->hasCache('Flow_Security_Authorization_Privilege_Method')) {
				$cacheManager->getCache('Flow_Security_Authorization_Privilege_Method')->flush();
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
	 * @param $stepMethodName string
	 * @param $encodedStepArguments string
	 */
	protected function callStepInSubProcess($stepMethodName, $encodedStepArguments = '', $withoutSecurityChecks = FALSE) {
		if (strpos($stepMethodName, '::') !== 0) {
			$stepMethodName = substr($stepMethodName, strpos($stepMethodName, '::') + 2);
		}
		$withoutSecurityChecks = ($withoutSecurityChecks === TRUE ? '--without-security-checks ' : '');
		$subProcessCommand = sprintf('typo3.flow.tests.functional:behathelper:callbehatstep %s%s%s', $withoutSecurityChecks, $stepMethodName, $encodedStepArguments);
		$subProcessResponse = $this->getSubProcess()->execute($subProcessCommand);

		Assert::assertStringStartsWith('SUCCESS:', $subProcessResponse, 'We called "' . $subProcessCommand . '" and got: '  . $subProcessResponse);
	}

	/**
	 * @AfterScenario
	 */
	public function quitSubProcess() {
		if ($this->subProcess !== NULL) {
			$this->subProcess->quit();
		}
	}
}
