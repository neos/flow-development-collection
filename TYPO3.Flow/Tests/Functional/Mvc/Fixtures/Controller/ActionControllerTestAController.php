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

/**
 * A controller fixture
 *
 * @Flow\Scope("singleton")
 */
class ActionControllerTestAController extends ActionController {

	/**
	 * @var array
	 */
	protected $supportedMediaTypes = array(
		'text/html', 'application/json'
	);

	/**
	 * @return string
	 */
	public function firstAction() {
		return 'First action was called';
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
	 * @param string $emailAddress
	 * @return void
	 */
	public function fourthAction($emailAddress) {
		$this->view->assign('emailAddress', $emailAddress);
	}

	/**
	 * @param string $putArgument
	 * @param string $getArgument
	 * @return string
	 */
	public function putAction($putArgument, $getArgument) {
		return "putAction-$putArgument-$getArgument";
	}

	/**
	 * @Flow\Validate("brokenArgument1", type="StringLength", options={"maximum": 3})
	 * @Flow\Validate("brokenArgument2", type="StringLength", options={"minimum": 100})
	 * @Flow\IgnoreValidation("brokenArgument1")
	 * @Flow\IgnoreValidation("$brokenArgument2")
	 * @param string $brokenArgument1
	 * @param string $brokenArgument2
	 * @return string
	 */
	public function ignoreValidationAction($brokenArgument1, $brokenArgument2) {
		return 'action was called';
	}
}
?>