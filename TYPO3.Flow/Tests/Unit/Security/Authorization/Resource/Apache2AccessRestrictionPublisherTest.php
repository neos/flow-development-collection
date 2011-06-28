<?php
namespace TYPO3\FLOW3\Security\Authorization\Resource;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Apache2 access restriction publisher
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Apache2AccessRestrictionPublisherTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function publishAccessRestrictionsForPathPublishesAHtaccessFileInTheGivenDirectory() {
		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRemoteAddress')->will($this->returnValue('192.168.1.234'));

		$publisher = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authorization\Resource\Apache2AccessRestrictionPublisher', array('dummy'));
		$publisher->_set('environment', $mockEnvironment);
		$publisher->publishAccessRestrictionsForPath('vfs://Foo/');

		$expectedFileContents = 'Deny from all' . chr(10) . 'Allow from 192.168.1.234';

		$this->assertFileExists('vfs://Foo/.htaccess');
		$this->assertEquals($expectedFileContents, file_get_contents('vfs://Foo/.htaccess'));
	}
}
