<?php
namespace TYPO3\Flow\Tests\Functional\Command;

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
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Security\Context;

/**
 * A command controller used to execute behat steps in an isolated process.
 * Note: This command controller will only be loaded in Testing context!
 *
 * @see IsolatedBehatStepsTrait
 *
 * @Flow\Scope("singleton")
 */
class BehatHelperCommandController extends CommandController {

	/**
	 * @var ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var PropertyMapper
	 * @Flow\Inject
	 */
	protected $propertyMapper;

	/**
	 * Calls a behat step method
	 *
	 * @Flow\Internal
	 * @param string $testHelperObjectName
	 * @param string $methodName
	 * @param boolean $withoutSecurityChecks
	 */
	public function callBehatStepCommand($testHelperObjectName, $methodName, $withoutSecurityChecks = FALSE) {
		$testHelper = $this->objectManager->get($testHelperObjectName);

		$rawMethodArguments = $this->request->getExceedingArguments();
		$mappedArguments = array();
		for ($i = 0; $i < count($rawMethodArguments); $i+=2) {
			$mappedArguments[] = $this->propertyMapper->convert($rawMethodArguments[$i+1], $rawMethodArguments[$i]);
		}

		$result = NULL;
		try {
			if ($withoutSecurityChecks === TRUE) {
				$this->securityContext->withoutAuthorizationChecks(function() use ($testHelper, $methodName, $mappedArguments, &$result) {
					$result = call_user_func_array(array($testHelper, $methodName), $mappedArguments);
				});
			} else {
				$result = call_user_func_array(array($testHelper, $methodName), $mappedArguments);
			}
		} catch (\Exception $exception) {
			$this->outputLine('EXCEPTION: %s %d %s in %s:%s %s', array(get_class($exception), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTraceAsString()));
			return;
		}
		$this->output('SUCCESS: %s', array($result));
	}
}
