<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Resource;

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
 * @version $Id:F3::FLOW3::Component::ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the resource manager
 *
 * @package FLOW3
 * @version $Id:F3::FLOW3::Component::ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ManagerTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::FLOW3::Resource::Manager
	 */
	protected $manager;

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$mockClassLoader = $this->getMock('F3::FLOW3::Resource::ClassLoader', array(), array(), '', FALSE);
		$this->manager = new F3::FLOW3::Resource::Manager($mockClassLoader, $this->componentFactory);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getResourceReturnsAResourceImplementation() {
		$resource = $this->manager->getResource('file://TestPackage/Public/TestTemplate.html');
		$this->assertType('F3::FLOW3::Resource::ResourceInterface', $resource);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getResourceReturnsRequestedResource() {
		$resource = $this->manager->getResource('file://TestPackage/Public/TestTemplate.html');
		$this->assertType('F3::FLOW3::Resource::HTMLResource', $resource);
		$this->assertEquals('TestTemplate.html', $resource->getName());
		$this->assertEquals('text/html', $resource->getMIMEType());
	}

}

?>