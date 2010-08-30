<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
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
 * Testcase for \F3\FLOW3\Persistence\Query
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class QueryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Persistence\Query
	 */
	protected $query;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Persistence\DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var \F3\FLOW3\Reflection\ClassSchema
	 */
	protected $classSchema;

	/**
	 * @var array
	 */
	protected $objectsData = array(array('identifier' => '2937d0a5-d8bd-4474-a3c8-96a1c8815f59', 'classname' => 'Some\Class\Name', 'properties' => array('foo' => array('type' => 'string', 'multivalue' => FALSE, 'value' => 'Foo')), array('identifier' => 'bd5106b8-a957-492b-b69d-d02620ace711', 'classname' => 'Some\Class\Name', 'properties' => array('foo' => array('type' => 'string', 'multivalue' => FALSE, 'value' => 'Bar')))));

	/**
	 * @var array
	 */
	protected $objects;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUp() {
		$this->objects = array(new \stdClass(), new \stdClass());
		$this->classSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array(), '', FALSE);
		$this->reflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$this->reflectionService->expects($this->any())->method('getClassSchema')->with('someType')->will($this->returnValue($this->classSchema));
		$this->persistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$this->objectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$this->dataMapper = $this->getMock('F3\FLOW3\Persistence\DataMapper');
		$this->query = new \F3\FLOW3\Persistence\Query('someType', $this->reflectionService);
		$this->query->injectPersistenceManager($this->persistenceManager);
		$this->query->injectObjectManager($this->objectManager);
		$this->query->injectDataMapper($this->dataMapper);
	}


	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function executeReturnsProxyByDefault() {
		$this->persistenceManager->expects($this->never())->method('getObjectDataByQuery');
		$this->dataMapper->expects($this->never())->method('mapToObjects');
		$resultProxy = $this->getMock('F3\FLOW3\Persistence\QueryResultProxy', array(), array(), '', FALSE);
		$this->objectManager->expects($this->once())->method('create')->will($this->returnValue($resultProxy));
		$result = $this->query->execute();
		$this->assertType('F3\FLOW3\Persistence\QueryResultProxy', $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function executePassesQueryObjectToProxyConstructorIfFetchModeIsProxy() {
		$resultProxy = $this->getMock('F3\FLOW3\Persistence\QueryResultProxy', array(), array(), '', FALSE);
		$this->objectManager->expects($this->once())->method('create')->with('F3\FLOW3\Persistence\QueryResultProxy', $this->query)->will($this->returnValue($resultProxy));
		$this->query->execute(\F3\FLOW3\Persistence\QueryInterface::FETCH_PROXY);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function executeReturnsArrayIfFetchModeIsArray() {
		$this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->will($this->returnValue($this->objectsData));
		$this->dataMapper->expects($this->once())->method('mapToObjects')->with($this->objectsData)->will($this->returnValue($this->objects));
		$actualResult = $this->query->execute(\F3\FLOW3\Persistence\QueryInterface::FETCH_ARRAY);
		$expectedResult = $this->objects;
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function executeReturnsEmptyArrayIfNoObjectWasFetched() {
		$this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->will($this->returnValue(array()));
		$this->dataMapper->expects($this->once())->method('mapToObjects')->with(array())->will($this->returnValue(array()));
		$actualResult = $this->query->execute(\F3\FLOW3\Persistence\QueryInterface::FETCH_ARRAY);
		$expectedResult = array();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function executeReturnsFirstResultIfFetchModeIsObject() {
		$this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->will($this->returnValue($this->objectsData));
		$this->dataMapper->expects($this->once())->method('mapToObjects')->with($this->objectsData)->will($this->returnValue($this->objects));
		$actualResult = $this->query->execute(\F3\FLOW3\Persistence\QueryInterface::FETCH_OBJECT);
		$expectedResult = $this->objects[0];
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function executeReturnsFalseIfFetchModeIsObjectAndObjectArrayIsEmpty() {
		$this->persistenceManager->expects($this->once())->method('getObjectDataByQuery')->with($this->query)->will($this->returnValue(array()));
		$this->dataMapper->expects($this->once())->method('mapToObjects')->with(array())->will($this->returnValue(array()));
		$actualResult = $this->query->execute(\F3\FLOW3\Persistence\QueryInterface::FETCH_OBJECT);
		$this->assertFalse($actualResult);
	}

}

?>