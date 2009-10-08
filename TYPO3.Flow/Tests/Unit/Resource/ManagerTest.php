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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$metaData = array(
			'mimeType' => 'text/html',
			'mediaType' => 'html',
			'URI' => 'file://FLOW3/Public/TestTemplate.html',
			'name' => 'TestTemplate.html',
			'path' => '',
		);

		$mockClassLoader = $this->getMock('F3\FLOW3\Resource\ClassLoader', array(), array(), '', FALSE);
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->at(0))->method('create')->with('F3\FLOW3\Property\DataType\URI')->will($this->returnValue(new \F3\FLOW3\Property\DataType\URI('file://FLOW3/Public/TestTemplate.html')));
		$mockObjectFactory->expects($this->at(1))->method('create')->with('F3\FLOW3\Resource\GenericResource')->will($this->returnValue(new \F3\FLOW3\Resource\GenericResource()));
		$mockResourcePublisher = $this->getMock('F3\FLOW3\Resource\Publisher', array(), array(), '', FALSE);
		$mockResourcePublisher->expects($this->any())->method('getMetadata')->will($this->returnValue($metaData));

		$this->manager = new \F3\FLOW3\Resource\Manager($mockClassLoader, $mockObjectFactory);
		$this->manager->injectResourcePublisher($mockResourcePublisher);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getResourceReturnsAResourceImplementation() {
		$resource = $this->manager->getResource('package://FLOW3/Public/TestTemplate.html');
		$this->assertType('F3\FLOW3\Resource\ResourceInterface', $resource);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getResourceReturnsRequestedResource() {
		$resource = $this->manager->getResource('package://FLOW3/Public/TestTemplate.html');
		$this->assertType('F3\FLOW3\Resource\GenericResource', $resource);
		$this->assertEquals('TestTemplate.html', $resource->getName());
		$this->assertEquals('text/html', $resource->getMIMEType());
	}

}

?>