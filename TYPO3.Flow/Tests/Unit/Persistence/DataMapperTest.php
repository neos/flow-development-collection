<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
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
 * Testcase for \F3\FLOW3\Persistence\DataMapper
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DataMapperTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapToObjectsMapsArrayToObjectByCallingmapToObject() {
		$objectData = array(array('identifier' => '1234'));
		$object = new \stdClass();

		$dataMapper = $this->getMock('F3\FLOW3\Persistence\DataMapper', array('mapToObject'));
		$dataMapper->expects($this->once())->method('mapToObject')->with($objectData[0])->will($this->returnValue($object));

		$dataMapper->mapToObjects($objectData);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapToObjectReturnsObjectFromIdentityMapIfAvailable() {
		$objectData = array('identifier' => '1234');
		$object = new \stdClass();

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockSession->expects($this->once())->method('hasIdentifier')->with('1234')->will($this->returnValue(TRUE));
		$mockSession->expects($this->once())->method('getObjectByIdentifier')->with('1234')->will($this->returnValue($object));

		$dataMapper = $this->getAccessibleMock('F3\FLOW3\Persistence\DataMapper', array('dummy'));
		$dataMapper->injectPersistenceSession($mockSession);
		$dataMapper->_call('mapToObject', $objectData);
	}

	/**
	 * Test that an object is reconstituted, registered with the identity map
	 * and memorizes it's clean state.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapToObjectReconstitutesExpectedObjectAndRegistersItWithIdentitymapToObjects() {
		$mockEntityClassName = uniqid('Entity');
		$mockEntity = $this->getMock('F3\FLOW3\AOP\ProxyInterface', array('FLOW3_AOP_Proxy_construct', 'FLOW3_AOP_Proxy_invokeJoinPoint', 'FLOW3_AOP_Proxy_hasProperty', 'FLOW3_AOP_Proxy_getProperty', 'FLOW3_AOP_Proxy_setProperty', 'FLOW3_AOP_Proxy_getProxyTargetClassName'));

		$objectData = array('identifier' => '1234', 'classname' => $mockEntityClassName, 'properties' => array('foo'));

		$mockClassSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array(), array(), '', FALSE);
		$mockClassSchema->expects($this->any())->method('getModelType')->will($this->returnValue(\F3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY));
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getClassSchema')->with($mockEntityClassName)->will($this->returnValue($mockClassSchema));
		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration\Configuration', array(), array(), '', FALSE);
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('recreate')->with($mockEntityClassName)->will($this->returnValue($mockEntity));
		$mockSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockSession->expects($this->once())->method('registerReconstitutedEntity')->with($mockEntity, $objectData);
		$mockSession->expects($this->once())->method('registerObject')->with($mockEntity, '1234');

		$dataMapper = $this->getAccessibleMock('F3\FLOW3\Persistence\DataMapper', array('thawProperties'));
		$dataMapper->expects($this->once())->method('thawProperties')->with($mockEntity, $objectData['identifier'], $objectData);
		$dataMapper->injectPersistenceSession($mockSession);
		$dataMapper->injectReflectionService($mockReflectionService);
		$dataMapper->injectObjectManager($mockObjectManager);
		$dataMapper->_call('mapToObject', $objectData);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function thawPropertiesSetsPropertyValues() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->at(0))->method('FLOW3_AOP_Proxy_setProperty')->with('firstProperty', 'firstValue');
		$object->expects($this->at(1))->method('FLOW3_AOP_Proxy_setProperty')->with('secondProperty', 1234);
		$object->expects($this->at(2))->method('FLOW3_AOP_Proxy_setProperty')->with('thirdProperty', 1.234);
		$object->expects($this->at(3))->method('FLOW3_AOP_Proxy_setProperty')->with('fourthProperty', FALSE);

		$objectData = array(
			'identifier' => '1234',
			'classname' => 'F3\Post',
			'properties' => array(
				'firstProperty' => array(
					'type' => 'string',
					'multivalue' => FALSE,
					'value' => 'firstValue'
				),
				'secondProperty' => array(
					'type' => 'integer',
					'multivalue' => FALSE,
					'value' => 1234
				),
				'thirdProperty' => array(
					'type' => 'float',
					'multivalue' => FALSE,
					'value' => 1.234
				),
				'fourthProperty' => array(
					'type' => 'boolean',
					'multivalue' => FALSE,
					'value' => FALSE
				)
			)
		);

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\Post');
		$classSchema->addProperty('firstProperty', 'string');
		$classSchema->addProperty('secondProperty', 'integer');
		$classSchema->addProperty('thirdProperty', 'float');
		$classSchema->addProperty('fourthProperty', 'boolean');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));

		$dataMapper = $this->getAccessibleMock('F3\FLOW3\Persistence\DataMapper', array('dummy'));
		$dataMapper->injectReflectionService($mockReflectionService);
		$dataMapper->_call('thawProperties', $object, 1234, $objectData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function thawPropertiesDoesNotSetAPropertyIfTheValueIsNULL() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->once())->method('FLOW3_AOP_Proxy_setProperty')->with('FLOW3_Persistence_Entity_UUID', 'c254d2e0-825a-11de-8a39-0800200c9a66');

		$objectData = array(
			'identifier' => 'c254d2e0-825a-11de-8a39-0800200c9a66',
			'classname' => 'F3\Post',
			'properties' => array(
				'firstProperty' => array(
					'type' => 'string',
					'multivalue' => FALSE,
					'value' => NULL
				)
			)
		);

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\Post');
		$classSchema->addProperty('firstProperty', 'string');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));

		$dataMapper = $this->getAccessibleMock('F3\FLOW3\Persistence\DataMapper', array('dummy'));
		$dataMapper->injectReflectionService($mockReflectionService);
		$dataMapper->_call('thawProperties', $object, $objectData['identifier'], $objectData);
	}

	/**
	 * After thawing the properties, the nodes' uuid will be available in the identifier
	 * property of the proxy class.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function thawPropertiesAssignsTheUuidToTheProxy() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->once())->method('FLOW3_AOP_Proxy_setProperty')->with('FLOW3_Persistence_Entity_UUID', 'c254d2e0-825a-11de-8a39-0800200c9a66');

		$objectData = array(
			'identifier' => 'c254d2e0-825a-11de-8a39-0800200c9a66',
			'classname' => 'F3\Post',
			'properties' => array()
		);

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\Post');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));

		$dataMapper = $this->getAccessibleMock('F3\FLOW3\Persistence\DataMapper', array('dummy'));
		$dataMapper->injectReflectionService($mockReflectionService);
		$dataMapper->_call('thawProperties', $object, $objectData['identifier'], $objectData);
	}

	/**
	 * After thawing the properties, the nodes' uuid will be available in the identifier
	 * property of the proxy class.
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function thawPropertiesAssignsTheUuidToTheDeclaredUuidPropertyInProxy() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->once())->method('FLOW3_AOP_Proxy_setProperty')->with('myUuidProperty', 'c254d2e0-825a-11de-8a39-0800200c9a66');

		$objectData = array(
			'identifier' => 'c254d2e0-825a-11de-8a39-0800200c9a66',
			'classname' => 'F3\Post',
			'properties' => array()
		);

		$classSchema = $this->getMock('F3\FLOW3\Reflection\ClassSchema', array('getUuidPropertyName'), array('F3\Post'));
		$classSchema->expects($this->once())->method('getUUIDPropertyName')->will($this->returnValue('myUuidProperty'));

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));

		$dataMapper = $this->getAccessibleMock('F3\FLOW3\Persistence\DataMapper', array('dummy'));
		$dataMapper->injectReflectionService($mockReflectionService);
		$dataMapper->_call('thawProperties', $object, $objectData['identifier'], $objectData);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function thawPropertiesDelegatesHandlingOfArraysAndObjects() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');

		$objectData = array(
			'identifier' => '1234',
			'classname' => 'F3\Post',
			'properties' => array(
				'firstProperty' => array(
					'type' => 'array',
					'multivalue' => TRUE,
					'value' => array(array('type' => 'string', 'index' => 0, 'value' => 'theMappedArray'))
				),
				'secondProperty' => array(
					'type' => 'SplObjectStorage',
					'multivalue' => TRUE,
					'value' => array(array('type' => 'Some\Object', 'index' => NULL, 'value' => 'theMappedSplObjectStorage'))
				),
				'thirdProperty' => array(
					'type' => 'DateTime',
					'multivalue' => FALSE,
					'value' => 'theUnixtime'
				),
				'fourthProperty' => array(
					'type' => '\F3\Some\Domain\Model',
					'multivalue' => FALSE,
					'value' => array('identifier' => 'theMappedObjectIdentifier')
				)
			)
		);

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\Post');
		$classSchema->addProperty('firstProperty', 'array');
		$classSchema->addProperty('secondProperty', 'SplObjectStorage');
		$classSchema->addProperty('thirdProperty', 'DateTime');
		$classSchema->addProperty('fourthProperty', '\F3\Some\Domain\Model');

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));

		$dataMapper = $this->getAccessibleMock('F3\FLOW3\Persistence\DataMapper', array('mapDateTime', 'mapArray', 'mapSplObjectStorage', 'mapToObject'));
		$dataMapper->injectReflectionService($mockReflectionService);
		$dataMapper->expects($this->at(0))->method('mapArray')->with($objectData['properties']['firstProperty']['value']);
		$dataMapper->expects($this->at(1))->method('mapSplObjectStorage')->with($objectData['properties']['secondProperty']['value']);
		$dataMapper->expects($this->at(2))->method('mapDateTime')->with($objectData['properties']['thirdProperty']['value']);
		$dataMapper->expects($this->at(3))->method('mapToObject')->with($objectData['properties']['fourthProperty']['value']);
		$dataMapper->_call('thawProperties', $object, $objectData['identifier'], $objectData);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapSplObjectStorageCreatesSplObjectStorage() {
		$objectData = array(
			array('value' => array('mappedObject1')),
			array('value' => array('mappedObject2'))
		);

		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\Post');
		$classSchema->addProperty('firstProperty', 'SplObjectStorage');

		$dataMapper = $this->getAccessibleMock('F3\FLOW3\Persistence\DataMapper', array('mapToObject'));
		$dataMapper->expects($this->at(0))->method('mapToObject')->with($objectData[0]['value'])->will($this->returnValue(new \stdClass()));
		$dataMapper->expects($this->at(1))->method('mapToObject')->with($objectData[1]['value'])->will($this->returnValue(new \stdClass()));
		$dataMapper->_call('mapSplObjectStorage', $objectData);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapDateTimeCreatesDateTimeFromTimestamp() {
		$expected = new \DateTime();
		$dataMapper = $this->getAccessibleMock('F3\FLOW3\Persistence\DataMapper', array('dummy'));
		$this->assertEquals($dataMapper->_call('mapDateTime', $expected->getTimestamp()), $expected);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapArrayCreatesExpectedArray() {
		$array = array('foo' => 'bar');
		$dateTime = new \DateTime();
		$object = new \stdClass();
		$splObjectStorage = new \SplObjectStorage();

		$expected = array(
			'one' => 'onetwothreefour',
			'two' => 1234,
			'three' => 1.234,
			'four' => FALSE,
			'five' => $dateTime,
			'six' => $object,
			'seven' => $splObjectStorage
		);

		$arrayValues = array(
			'one' => array(
				'type' => 'string',
				'index' => 'one',
				'value' => 'onetwothreefour'
			),
			'two' => array(
				'type' => 'integer',
				'index' => 'two',
				'value' => 1234
			),
			'three' => array(
				'type' => 'float',
				'index' => 'three',
				'value' =>  1.234
			),
			'four' => array(
				'type' => 'boolean',
				'index' => 'four',
				'value' => FALSE
			),
			'five' => array(
				'type' => 'DateTime',
				'index' => 'five',
				'value' => $dateTime->getTimestamp()
			),
			'six' => array(
				'type' => 'stdClass',
				'index' => 'six',
				'value' => array('mappedObject')
			),
			'seven' => array(
				'type' => 'SplObjectStorage',
				'index' => 'seven',
				'value' => array('mappedObject')
			)
		);

		$dataMapper = $this->getAccessibleMock('F3\FLOW3\Persistence\DataMapper', array('mapDateTime', 'mapToObject', 'mapSplObjectStorage'));
		$dataMapper->expects($this->once())->method('mapDateTime')->with($arrayValues['five']['value'])->will($this->returnValue($dateTime));
		$dataMapper->expects($this->once())->method('mapToObject')->with($arrayValues['six']['value'])->will($this->returnValue($object));
		$dataMapper->expects($this->once())->method('mapSplObjectStorage')->with($arrayValues['seven']['value'])->will($this->returnValue($splObjectStorage));
		$this->assertEquals($dataMapper->_call('mapArray', $arrayValues), $expected);
	}


	/**
	 * @test
	 * @expectedException \RuntimeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapArrayThrowsExceptionOnNestedArray() {
		$arrayValues = array(
			'one' => array(
				'type' => 'array',
				'index' => 0,
				'value' => 'foo'
			)
		);
		$dataMapper = $this->getAccessibleMock('F3\FLOW3\Persistence\DataMapper', array('dummy'));
		$dataMapper->_call('mapArray', $arrayValues);
	}
}

?>