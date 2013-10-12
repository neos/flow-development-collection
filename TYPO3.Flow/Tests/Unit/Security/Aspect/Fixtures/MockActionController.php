<?php
namespace TYPO3\Flow\Security\Aspect\Fixtures;

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

/**
 * A mock ActionController
 *
 */
class MockActionController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @return void
	 */
	public function actionWithCsrfProtectionAction() {
	}

	/**
	 * @Flow\SkipCsrfProtection
	 * @return void
	 */
	public function actionWithoutCsrfProtectionAction() {
	}
}
