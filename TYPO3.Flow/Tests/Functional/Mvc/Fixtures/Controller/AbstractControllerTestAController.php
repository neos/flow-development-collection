<?php
namespace TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller;

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
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Error\Message;

/**
 * A controller fixture for testing the AbstractController functionality.
 *
 * Althought the functions we want to test are really implemented in the Abstract
 * Controller, this fixture class is an ActionController as this is the easiest way
 * to provide an implementation of the abstract class.
 */
class AbstractControllerTestAController extends ActionController {

	/**
	 * An action which forwards using the given parameters
	 *
	 * @param string $actionName
	 * @param string $controllerName
	 * @param string $packageKey
	 * @param array $arguments
	 * @param boolean $passSomeObjectArguments
	 * @return void
	 */
	public function forwardAction($actionName, $controllerName = NULL, $packageKey = NULL, array $arguments = array(), $passSomeObjectArguments = FALSE) {
		if ($passSomeObjectArguments) {
			$arguments['__object1'] = new Message('Some test message', 12345);
			$arguments['__object1'] = new Message('Some test message', 67890);
		}
		$this->forward($actionName, $controllerName, $packageKey, $arguments);
	}

	/**
	 * @return string
	 */
	public function secondAction() {
		return 'Second action was called';
	}

	/**
	 * @param string $firstArgument
	 * @param string $secondArgument
	 * @param string $third
	 * @param string $fourth
	 * @return string
	 */
	public function thirdAction($firstArgument, $secondArgument, $third = NULL, $fourth = 'default') {
		return "thirdAction-$firstArgument-$secondArgument-$third-$fourth";
	}

	/**
	 *
	 * @param string $nonObject1
	 * @param integer $nonObject2
	 * @return string
	 */
	public function fourthAction($nonObject1 = NULL, $nonObject2 = NULL) {
		$internalArguments = $this->request->getInternalArguments();
		return "fourthAction-$nonObject1-$nonObject2-" . (isset($internalArguments['__object1']) ? get_class($internalArguments['__object1']) : 'x');
	}
}
