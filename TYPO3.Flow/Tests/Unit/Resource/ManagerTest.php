<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource;

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
 * Testcase for the resource manager
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Resource\Manager
	 */
	protected $manager;

	/**
	 * This test indeed messes with some of the static stuff concerning our
	 * StreamWrapper setup. But since the dummy stream wrapper is removed again,
	 * this does not do any harm. And registering the "real" wrappers a second
	 * time doesn't do harm, either.
	 * 
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeStreamWrappersRegistersFoundStreamWrappers() {
		$wrapperClassName = uniqid('MockWrapper');
		$wrapperSchemeName = $wrapperClassName . 'scheme';
		eval('class ' . $wrapperClassName . ' extends \F3\FLOW3\Resource\PackageStreamWrapper { static public function getScheme() { return \'' . $wrapperSchemeName . '\'; } }');
		$mockStreamWrapper = new $wrapperClassName();

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service');
		$mockReflectionService->expects($this->once())->method('getAllImplementationClassNamesForInterface')->with('F3\FLOW3\Resource\StreamWrapperInterface')->will($this->returnValue(array(get_class($mockStreamWrapper))));

		$resourceManager = new \F3\FLOW3\Resource\Manager(array());
		$resourceManager->injectObjectFactory($mockObjectFactory);
		$resourceManager->injectReflectionService($mockReflectionService);
		$resourceManager->initializeStreamWrappers();

		$this->assertContains(get_class($mockStreamWrapper), \F3\FLOW3\Resource\StreamWrapper::getRegisteredStreamWrappers());
		$this->assertArrayHasKey($wrapperSchemeName, \F3\FLOW3\Resource\StreamWrapper::getRegisteredStreamWrappers());
		$this->assertContains($wrapperSchemeName, stream_get_wrappers());
		stream_wrapper_unregister($wrapperSchemeName);
	}

}

?>