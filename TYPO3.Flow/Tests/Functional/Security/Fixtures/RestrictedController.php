<?php
namespace TYPO3\FLOW3\Tests\Functional\Security\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A controller for functional testing
 *
 */
class RestrictedController extends \TYPO3\FLOW3\Mvc\Controller\ActionController {

	/**
	 * @return void
	 */
	public function publicAction() {}

	/**
	 * @return void
	 */
	public function customerAction() {}

	/**
	 * @return void
	 */
	public function adminAction() {}
}
?>