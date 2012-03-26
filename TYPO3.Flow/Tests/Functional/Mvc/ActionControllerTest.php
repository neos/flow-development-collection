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

/**
 * Functional tests for the ActionController
 */
class ActionControllerTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function ignoreValidationAnnotationsAreHandledCorrectly() {
		$arguments = array(
			'argument' => array(
				'name' => 'Foo',
				'emailAddress' => '-invalid-'
			)
		);
		$result = $this->sendWebRequest('Testing', 'TYPO3.FLOW3\Tests\Functional\Mvc\Fixtures', 'showObjectArgument', $arguments);

		$this->assertEquals('-invalid-', $result, 'Action should process with invalid argument');
	}

}
?>