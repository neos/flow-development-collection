<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

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
 * Testcase for the Persistence Session
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SessionTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function objectRegisteredWithRegisterReconstitutedEntityCanBeRetrievedWithGetReconstitutedEntities() {
		$someObject = new \ArrayObject();
		$session = new \F3\FLOW3\Persistence\Session();
		$session->registerReconstitutedEntity($someObject, array('identifier' => 'fakeUuid'));

		$ReconstitutedEntities = $session->getReconstitutedEntities();
		$this->assertTrue($ReconstitutedEntities->contains($someObject));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unregisterReconstitutedEntityRemovesObjectFromSession() {
		$someObject = new \ArrayObject();
		$session = new \F3\FLOW3\Persistence\Session();
		$session->registerObject($someObject, 'fakeUuid');
		$session->registerReconstitutedEntity($someObject, array('identifier' => 'fakeUuid'));
		$session->unregisterReconstitutedEntity($someObject);

		$ReconstitutedEntities = $session->getReconstitutedEntities();
		$this->assertFalse($ReconstitutedEntities->contains($someObject));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasObjectReturnsTrueForRegisteredObject() {
		$object1 = new \stdClass();
		$object2 = new \stdClass();
		$session = new \F3\FLOW3\Persistence\Session();
		$session->registerObject($object1, 12345);

		$this->assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
		$this->assertFalse($session->hasObject($object2), 'Session claims it does have unregistered object.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasIdentifierReturnsTrueForRegisteredObject() {
		$session = new \F3\FLOW3\Persistence\Session();
		$session->registerObject(new \stdClass(), 12345);

		$this->assertTrue($session->hasIdentifier('12345'), 'Session claims it does not have registered object.');
		$this->assertFalse($session->hasIdentifier('67890'), 'Session claims it does have unregistered object.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObjectReturnsRegisteredUUIDForObject() {
		$object = new \stdClass();
		$session = new \F3\FLOW3\Persistence\Session();
		$session->registerObject($object, 12345);

		$this->assertEquals($session->getIdentifierByObject($object), 12345, 'Did not get UUID registered for object.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectByIdentifierReturnsRegisteredObjectForUUID() {
		$object = new \stdClass();
		$session = new \F3\FLOW3\Persistence\Session();
		$session->registerObject($object, 12345);

		$this->assertSame($session->getObjectByIdentifier('12345'), $object, 'Did not get object registered for UUID.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unregisterObjectRemovesRegisteredObject() {
		$object1 = new \stdClass();
		$object2 = new \stdClass();
		$session = new \F3\FLOW3\Persistence\Session();
		$session->registerObject($object1, 12345);
		$session->registerObject($object2, 67890);

		$this->assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
		$this->assertTrue($session->hasIdentifier('12345'), 'Session claims it does not have registered object.');
		$this->assertTrue($session->hasObject($object1), 'Session claims it does not have registered object.');
		$this->assertTrue($session->hasIdentifier('67890'), 'Session claims it does not have registered object.');

		$session->unregisterObject($object1);

		$this->assertFalse($session->hasObject($object1), 'Session claims it does have unregistered object.');
		$this->assertFalse($session->hasIdentifier('12345'), 'Session claims it does not have registered object.');
		$this->assertTrue($session->hasObject($object2), 'Session claims it does not have registered object.');
		$this->assertTrue($session->hasIdentifier('67890'), 'Session claims it does not have registered object.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function newObjectsAreConsideredDirty() {
		$session = new \F3\FLOW3\Persistence\Session();
		$this->assertTrue($session->isDirty(new \stdClass(), 'foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyReturnsTrueForUnregisteredReconstitutedEntities() {
		$session = $this->getMock('F3\FLOW3\Persistence\Session', array('isReconstitutedEntity'));
		$session->expects($this->once())->method('isReconstitutedEntity')->will($this->returnValue(FALSE));
		$this->assertTrue($session->isDirty(new \stdClass(), 'foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyReturnsFalseForNullInBothCurrentAndCleanValue() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->will($this->returnValue(NULL));

		$session = $this->getMock('F3\FLOW3\Persistence\Session', array('getIdentifierByObject'));
		$session->registerReconstitutedEntity($object, array('identifier' => 'fakeUuid'));
		$session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));

		$this->assertFalse($session->isDirty($object, 'foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyAsksIsPropertyDirtyForChangedLiterals() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->atLeastOnce())->method('FLOW3_AOP_Proxy_getProperty')->with('foo')->will($this->returnValue('different'));

		$cleanData = array(
			'identifier' => 'fakeUuid',
			'properties' => array(
				'foo' => array(
					'type' => 'string',
					'multivalue' => FALSE,
					'value' => 'bar'
				)
			)
		);
		$session = $this->getMock('F3\FLOW3\Persistence\Session', array('getIdentifierByObject', 'isPropertyDirty'));
		$session->registerReconstitutedEntity($object, $cleanData);
		$session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));
		$session->expects($this->once())->method('isPropertyDirty')->with('string', 'bar', 'different')->will($this->returnValue(TRUE));

		$this->assertTrue($session->isDirty($object, 'foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyReturnsFalseForUnactivatedLazyObjects() {
		$object = new \stdClass();
		$object->FLOW3_Persistence_LazyLoadingObject_thawProperties = 'dummy';

		$session = $this->getMock('F3\FLOW3\Persistence\Session', array('dummy'));
		$session->registerReconstitutedEntity($object, array('identifier' => 'fakeUuid'));
		$this->assertFalse($session->isDirty($object, 'foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyReturnsTrueForTraversablesWhoseCountDiffers() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->will($this->returnValue(array('foo', 'bar', 'baz')));

		$cleanData = array(
			'identifier' => 'fakeUuid',
			'properties' => array(
				'foo' => array(
					'type' => 'string',
					'multivalue' => TRUE,
					'value' => array(array(), array())
				)
			)
		);
		$session = $this->getMock('F3\FLOW3\Persistence\Session', array('getIdentifierByObject'));
		$session->registerReconstitutedEntity($object, $cleanData);
		$session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));

		$this->assertTrue($session->isDirty($object, 'foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyReturnsTrueForNestedArrayWhoseCountDiffers() {
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->will($this->returnValue(array('foo', array('bar', 'baz'))));

		$cleanData = array(
			'identifier' => 'fakeUuid',
			'properties' => array(
				'foo' => array(
					'type' => 'string',
					'multivalue' => TRUE,
					'value' => array(
						array('type' => 'string', 'index' => 0, 'value' => 'foo'),
						array(
							'type' => 'array',
							'index' => 1,
							'value' => array('type' => 'string', 'index' => 0, 'value' => 'bar'),
						)
					)
				)
			)
		);
		$session = $this->getMock('F3\FLOW3\Persistence\Session', array('getIdentifierByObject'));
		$session->registerReconstitutedEntity($object, $cleanData);
		$session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));

		$this->assertTrue($session->isDirty($object, 'foo'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyReturnsTrueForSplObjectStorageWhoseContainedObjectsDiffer() {
		$object = new \stdClass();
		$object->FLOW3_Persistence_Entity_UUID = 'dirtyUuid';
		$splObjectStorage = new \SplObjectStorage();
		$splObjectStorage->attach($object);
		$parent = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$parent->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->with('splObjectStorage')->will($this->returnValue($splObjectStorage));

		$cleanData = array(
			'identifier' => 'fakeUuid',
			'properties' => array(
				'splObjectStorage' => array(
					'type' => 'SplObjectStorage',
					'multivalue' => TRUE,
					'value' => array(
						array(
							'value' => array('identifier' => 'cleanUuid')
						)
					)
				)
			)
		);
		$session = $this->getMock('F3\FLOW3\Persistence\Session', array('getIdentifierByObject'));
		$session->registerReconstitutedEntity($parent, $cleanData);
		$session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));

		$this->assertTrue($session->isDirty($parent, 'splObjectStorage'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyReturnsTrueForArraysWhoseContainedObjectsDiffer() {
		$object = new \stdClass();
		$object->FLOW3_Persistence_Entity_UUID = 'dirtyUuid';
		$array = array();
		$array[] = $object;
		$parent = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$parent->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->with('array')->will($this->returnValue($array));

		$cleanData = array(
			'identifier' => 'fakeUuid',
			'properties' => array(
				'array' => array(
					'type' => 'array',
					'multivalue' => TRUE,
					'value' => array(
						array(
							'type' => 'Some\Object',
							'index' => 0,
							'value' => array('identifier' => 'cleanUuid')
						)
					)
				)
			)
		);
		$session = $this->getMock('F3\FLOW3\Persistence\Session', array('getIdentifierByObject', 'isPropertyDirty'));
		$session->registerReconstitutedEntity($parent, $cleanData);
		$session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));
		$session->expects($this->once())->method('isPropertyDirty')->will($this->returnValue(TRUE));

		$this->assertTrue($session->isDirty($parent, 'array'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyReturnsFalseForCleanArrays() {
		$object = new \stdClass();
		$object->FLOW3_Persistence_ValueObject_Hash = 'cleanHash';
		$array = array();
		$array[] = $object;
		$parent = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$parent->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->with('array')->will($this->returnValue($array));

		$cleanData = array(
			'identifier' => 'fakeUuid',
			'properties' => array(
				'array' => array(
					'type' => 'array',
					'multivalue' => TRUE,
					'value' => array(
						array(
							'type' => 'Some\Object',
							'index' => 0,
							'value' => array('identifier' => 'cleanHash')
						)
					)
				)
			)
		);
		$session = $this->getMock('F3\FLOW3\Persistence\Session', array('getIdentifierByObject', 'isPropertyDirty'));
		$session->registerReconstitutedEntity($parent, $cleanData);
		$session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));
		$session->expects($this->once())->method('isPropertyDirty')->will($this->returnValue(FALSE));

		$this->assertFalse($session->isDirty($parent, 'array'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyReturnsFalseForCleanNestedArrays() {
		$object = new \stdClass();
		$object->FLOW3_Persistence_ValueObject_Hash = 'cleanHash';
		$array = array(array($object));
		$parent = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$parent->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->with('array')->will($this->returnValue($array));

		$cleanData = array(
			'identifier' => 'fakeUuid',
			'properties' => array(
				'array' => array(
					'type' => 'array',
					'multivalue' => TRUE,
					'value' => array(
						array(
							'type' => 'array',
							'index' => 0,
							'value' => array(
								array(
									'type' => 'Some\Object',
									'index' => 0,
									'value' => array('identifier' => 'cleanHash')
								),
							)
						)
					)
				)
			)
		);
		$session = $this->getMock('F3\FLOW3\Persistence\Session', array('getIdentifierByObject', 'isPropertyDirty'));
		$session->registerReconstitutedEntity($parent, $cleanData);
		$session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));
		$session->expects($this->once())->method('isPropertyDirty')->will($this->returnValue(FALSE));

		$this->assertFalse($session->isDirty($parent, 'array'));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyReturnsTrueForArraysWithNewMembers() {
		$object = new \stdClass();
		$object->FLOW3_Persistence_Entity_UUID = 'dirtyUuid';
		$array = array();
		$array[] = $object;
		$parent = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$parent->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->with('array')->will($this->returnValue($array));

		$cleanData = array(
			'identifier' => 'fakeUuid',
			'properties' => array(
				'array' => array(
					'type' => 'array',
					'multivalue' => TRUE,
					'value' => array(
						array(
							'type' => 'Some\Object',
							'index' => 'new',
							'value' => array('identifier' => 'cleanUuid')
						)
					)
				)
			)
		);
		$session = $this->getMock('F3\FLOW3\Persistence\Session', array('getIdentifierByObject'));
		$session->registerReconstitutedEntity($parent, $cleanData);
		$session->expects($this->once())->method('getIdentifierByObject')->will($this->returnValue('fakeUuid'));

		$this->assertTrue($session->isDirty($parent, 'array'));
	}

	/**
	 * Returns tuples of the form <type, current, clean, expected> for
	 * isPropertyDirty()
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function propertyData() {
		$dateTime = new \DateTime();
		$entity = new \stdClass();
		$entity->FLOW3_Persistence_Entity_UUID = 'cleanUuid';
		$valueObject = new \stdClass();
		$valueObject->FLOW3_Persistence_ValueObject_Hash = 'cleanHash';

		return array(
			array('string', 'foo', 'foo', FALSE),
			array('string', 'foo', 'bar', TRUE),
			array('boolean', TRUE, TRUE, FALSE),
			array('boolean', TRUE, FALSE, TRUE),
			array('float', 1.2, 1.2, FALSE),
			array('float', 1.2, 1.3, TRUE),
			array('integer', 10, 10, FALSE),
			array('integer', 10, 12, TRUE),
			array('Some\Entity', $entity, array('identifier' => 'cleanUuid'), FALSE),
			array('Some\Entity', $entity, array('identifier' => 'dirtyUuid'), TRUE),
			array('Some\ValueObject', $valueObject, array('identifier' => 'cleanHash'), FALSE),
			array('Some\ValueObject', $valueObject, array('identifier' => 'dirtyHash'), TRUE),
			array('DateTime', $dateTime, $dateTime->getTimestamp(), FALSE),
			array('DateTime', $dateTime, $dateTime->getTimestamp()+1, TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider propertyData
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isPropertyDirtyWorksAsExpected($type, $current, $clean, $expected) {
		$session = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Session'), array('dummy'));
		$this->assertEquals($session->_call('isPropertyDirty', $type, $clean, $current), $expected);
	}
}
?>