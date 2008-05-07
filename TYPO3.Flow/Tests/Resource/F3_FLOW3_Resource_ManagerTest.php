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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_Component_ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the resource manager
 *
 * @package FLOW3
 * @version $Id:F3_FLOW3_Component_ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Resource_ManagerTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_FLOW3_Resource_Manager
	 */
	protected $manager;

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$mockClassLoader = $this->getMock('F3_FLOW3_Resource_ClassLoader', array(), array(), '', FALSE);
		$this->manager = new F3_FLOW3_Resource_Manager($mockClassLoader, $this->componentManager);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getResourceReturnsAResourceImplementation() {
		$resource = $this->manager->getResource('file://TestPackage/Public/TestTemplate.html');
		$this->assertType('F3_FLOW3_Resource_ResourceInterface', $resource);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getResourceReturnsRequestedResource() {
		$resource = $this->manager->getResource('file://TestPackage/Public/TestTemplate.html');
		$this->assertType('F3_FLOW3_Resource_HTMLResource', $resource);
		$this->assertEquals('TestTemplate.html', $resource->getName());
		$this->assertEquals('text/html', $resource->getMIMEType());
	}

}

?>