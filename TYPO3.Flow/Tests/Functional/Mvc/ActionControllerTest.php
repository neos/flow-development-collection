<?php
namespace TYPO3\FLOW3\Tests\Functional\Mvc;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Http\Client\Browser;

/**
 * Functional tests for the ActionController
 */
class ActionControllerTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function actionIsCalledAccordingToActionRequestAndSimpleResponseIsReturned() {
		var_dump('x');
		return '';
#		$browser = new Browser();
		$response = $browser->request('http://localhost/test/mvc/actioncontrollertesta/first');

	}

}
?>
