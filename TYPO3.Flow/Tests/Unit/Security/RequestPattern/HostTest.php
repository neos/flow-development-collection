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
class HostTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Data provider with URIs and host patterns
	 */
	public function uriAndHostPatterns() {
		return array(
			array('http://typo3.org/index.php', 'typo3.*', TRUE, 'Assert that wildcard matches.'),
			array('http://typo3.org/index.php', 'flow.typo3.org', FALSE, 'Assert that subdomains don\'t match.'),
			array('http://typo3.org/index.php', '*typo3.org', TRUE, 'Assert that prefix wildcard matches.'),
			array('http://typo3.org/index.php', '*.typo3.org', FALSE, 'Assert that subdomain wildcard doesn\'t match.'),
			array('http://flow.typo3.org/', '*.typo3.org', TRUE, 'Assert that subdomain wildcard matches.'),
			array('http://flow.typo3.org/', 'neos.typo3.org', FALSE, 'Assert that different subdomain doesn\'t match.'),
		);
	}

	/**
	 * @dataProvider uriAndHostPatterns
	 * @test
	 */
	public function requestMatchingBasicallyWorks($uri, $pattern, $expected, $message) {
		$request = Request::create(new \TYPO3\Flow\Http\Uri($uri))->createActionRequest();

		$requestPattern = new \TYPO3\Flow\Security\RequestPattern\Host();
		$requestPattern->setPattern($pattern);

		$this->assertEquals($expected, $requestPattern->matchRequest($request), $message);
	}
}
?>