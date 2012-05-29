<?php
namespace TYPO3\FLOW3\Tests\Functional\Mvc\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Mvc\Controller\ActionController;

/**
 * A controller fixture
 *
 * @FLOW3\Scope("singleton")
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
}
?>