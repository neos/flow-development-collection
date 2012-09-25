<?php
namespace TYPO3\Flow\Tests\Unit\Security\RequestPattern;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Request;

/**
 * Testcase for the URI request pattern
 */
class UriTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function requestMatchingBasicallyWorks() {
		$uri = new \TYPO3\Flow\Http\Uri('http://typo3.org/some/nice/path/to/index.php');
		$request = Request::create($uri)->createActionRequest();

		$requestPattern = new \TYPO3\Flow\Security\RequestPattern\Uri();
		$requestPattern->setPattern('/some/nice/.*');

		$this->assertEquals('/some/nice/.*', $requestPattern->getPattern());
		$this->assertTrue($requestPattern->matchRequest($request));
	}
}
?>