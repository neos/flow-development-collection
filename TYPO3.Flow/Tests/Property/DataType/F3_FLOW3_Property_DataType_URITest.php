<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Testcase for the MVC URI class
 * 
 * @package		FLOW3
 * @version 	$Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Property_DataType_URITest extends F3_Testing_BaseTestCase {
	
	/**
	 * Checks if a complete URI with all parts is transformed into an object correctly.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorParsesAFullBlownURIStringCorrectly() {
		$URIString = 'http://username:password@subdomain.domain.com:8080/path1/path2/index.php?argument1=value1&argument2=value2&argument3[subargument1]=subvalue1#anchor';
		$URI = new F3_FLOW3_Property_DataType_URI($URIString);

		$check = (
			$URI->getScheme() == 'http' &&
			$URI->getUsername() == 'username' &&
			$URI->getPassword() == 'password' &&
			$URI->getHost() == 'subdomain.domain.com' &&
			$URI->getPort() === 8080 &&
			$URI->getPath() == '/path1/path2/index.php' &&
			$URI->getQuery() == 'argument1=value1&argument2=value2&argument3[subargument1]=subvalue1' &&
			$URI->getArguments() == array('argument1' => 'value1', 'argument2' => 'value2', 'argument3' => array('subargument1' => 'subvalue1')) &&
			$URI->getFragment() == 'anchor'
		);
		$this->assertTrue($check, 'The valid and complete URI has not been correctly transformed to an URI object');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org> 
	 */
	public function constructorParsesArgumentsWithSpecialCharactersCorrectly() {
		$URIString = 'http://www.typo3.com/path1/?argumentäöü1=' . urlencode('valueåø€œ');
		$URI = new F3_FLOW3_Property_DataType_URI($URIString);

		$check = (
			$URI->getScheme() == 'http' &&
			$URI->getHost() == 'www.typo3.com' &&
			$URI->getPath() == '/path1/' &&
			$URI->getQuery() == 'argumentäöü1=value%C3%A5%C3%B8%E2%82%AC%C5%93' &&
			$URI->getArguments() == array('argumentäöü1' => 'valueåø€œ')
		);
		$this->assertTrue($check, 'The URI with special arguments has not been correctly transformed to an URI object');
	}

	/**
	 * Checks if a complete URI with all parts is transformed into an object correctly.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function stringRepresentationIsCorrect() {
		$URIString = 'http://username:password@subdomain.domain.com:1234/pathx1/pathx2/index.php?argument1=value1&argument2=value2&argument3[subargument1]=subvalue1#anchorman';
		$URI = new F3_FLOW3_Property_DataType_URI($URIString);
		$this->assertEquals($URIString, (string)$URI, 'The string representation of the URI is not equal to the original URI string.');
	}
}
?>