<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Backend;

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

require_once(__DIR__ . '/../Fixture/AnEntity.php');
require_once(__DIR__ . '/../Fixture/AValue.php');

/**
 * Testcase for \F3\FLOW3\Persistence\Backend
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractBackendTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeAsksReflectionServiceForClassSchemata() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getClassSchemata');
		$backend = $this->getMockForAbstractClass('F3\FLOW3\Persistence\Backend\AbstractBackend');
		$backend->injectReflectionService($mockReflectionService);
		$backend->initialize(array());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function commitDelegatesToPersistObjectsAndProcessDeletedObjects() {
		$backend = $this->getMock('F3\FLOW3\Persistence\Backend\AbstractBackend', array('persistObjects', 'processDeletedObjects', 'getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier'));
		$backend->expects($this->once())->method('persistObjects');
		$backend->expects($this->once())->method('processDeletedObjects');
		$backend->commit();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function persistObjectsPassesObjectsToPersistObject() {
		$objects = new \SplObjectStorage();
		$objects->attach(new \stdClass());
		$objects->attach(new \stdClass());

		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceSession->expects($this->once())->method('getReconstitutedEntities')->will($this->returnValue(new \SplObjectStorage));
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\AbstractBackend'), array('persistObject', 'getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier'));

		$backend->injectPersistenceSession($mockPersistenceSession);
		$backend->expects($this->exactly(2))->method('persistObject');
		$backend->setAggregateRootObjects($objects);
		$backend->_call('persistObjects');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function processDeletedObjectsPassesObjectsToRemoveEntity() {
		$object = new \stdClass();
		$objects = new \SplObjectStorage();
		$objects->attach($object);

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockSession->expects($this->at(0))->method('hasObject')->with($object)->will($this->returnValue(TRUE));
		$mockSession->expects($this->at(1))->method('unregisterReconstitutedEntity')->with($object);
		$mockSession->expects($this->at(2))->method('unregisterObject')->with($object);

		$backend = $this->getAccessibleMock('F3\FLOW3\Persistence\Backend\AbstractBackend', array('removeEntity', 'getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier'));
		$backend->injectPersistenceSession($mockSession);
		$backend->expects($this->once())->method('removeEntity')->with($object);
		$backend->setDeletedEntities($objects);
		$backend->_call('processDeletedObjects');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function processDeletedObjectsPassesOnlyKnownObjectsToRemoveEntity() {
		$object = new \stdClass();
		$objects = new \SplObjectStorage();
		$objects->attach($object);

		$mockSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockSession->expects($this->at(0))->method('hasObject')->with($object)->will($this->returnValue(FALSE));
		$mockSession->expects($this->never())->method('unregisterObject');

		$backend = $this->getAccessibleMock('F3\FLOW3\Persistence\Backend\AbstractBackend', array('removeEntity', 'getObjectCountByQuery', 'getObjectDataByQuery', 'getObjectDataByIdentifier'));
		$backend->injectPersistenceSession($mockSession);
		$backend->expects($this->never())->method('removeEntity');
		$backend->setDeletedEntities($objects);
		$backend->_call('processDeletedObjects');
	}

	/**
	 * Does it return the UUID for an object know to the identity map?
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObjectReturnsUUIDForKnownObject() {
		$knownObject = new \stdClass();
		$fakeUUID = '123-456';

		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceSession->expects($this->once())->method('hasObject')->with($knownObject)->will($this->returnValue(TRUE));
		$mockPersistenceSession->expects($this->once())->method('getIdentifierByObject')->with($knownObject)->will($this->returnValue($fakeUUID));
		$backend = $this->getMockForAbstractClass($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\AbstractBackend'));
		$backend->injectPersistenceSession($mockPersistenceSession);

		$this->assertEquals($fakeUUID, $backend->_call('getIdentifierByObject', $knownObject));
	}

	/**
	 * Does it return the UUID for an AOP proxy not being in the identity map
	 * but having FLOW3_Persistence_Entity_UUID?
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObjectReturnsUuidForObjectBeingAOPProxy() {
		$knownObject = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$knownObject->expects($this->once())->method('FLOW3_AOP_Proxy_hasProperty')->with('FLOW3_Persistence_Entity_UUID')->will($this->returnValue(TRUE));
		$knownObject->expects($this->once())->method('FLOW3_AOP_Proxy_getProperty')->with('FLOW3_Persistence_Entity_UUID')->will($this->returnValue('fakeUUID'));
		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceSession->expects($this->once())->method('hasObject')->with($knownObject)->will($this->returnValue(FALSE));

		$backend = $this->getMockForAbstractClass($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\AbstractBackend'));
		$backend->injectPersistenceSession($mockPersistenceSession);

		$this->assertEquals('fakeUUID', $backend->_call('getIdentifierByObject', $knownObject));
	}

	/**
	 * Does it return the value object hash for an AOP proxy not being in the
	 * identity map but having FLOW3_Persistence_ValueObject_Hash?
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObjectReturnsHashForObjectBeingAOPProxy() {
		$knownObject = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$knownObject->expects($this->at(0))->method('FLOW3_AOP_Proxy_hasProperty')->with('FLOW3_Persistence_Entity_UUID')->will($this->returnValue(FALSE));
		$knownObject->expects($this->at(1))->method('FLOW3_AOP_Proxy_hasProperty')->with('FLOW3_Persistence_ValueObject_Hash')->will($this->returnValue(TRUE));
		$knownObject->expects($this->once())->method('FLOW3_AOP_Proxy_getProperty')->with('FLOW3_Persistence_ValueObject_Hash')->will($this->returnValue('fakeHash'));
		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceSession->expects($this->once())->method('hasObject')->with($knownObject)->will($this->returnValue(FALSE));

		$backend = $this->getMockForAbstractClass($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\AbstractBackend'));
		$backend->injectPersistenceSession($mockPersistenceSession);

		$this->assertEquals('fakeHash', $backend->_call('getIdentifierByObject', $knownObject));
	}

	/**
	 * Does it work for objects not being an AOP proxy, i.e. not having the
	 * method FLOW3_AOP_Proxy_getProperty() and not known to the identity map?
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObjectReturnsNullForUnknownObjectBeingPOPO() {
		$unknownObject = new \stdClass();
		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceSession->expects($this->once())->method('hasObject')->with($unknownObject)->will($this->returnValue(FALSE));

		$backend = $this->getMockForAbstractClass($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\AbstractBackend'));
		$backend->injectPersistenceSession($mockPersistenceSession);

		$this->assertNull($backend->_call('getIdentifierByObject', $unknownObject));
	}

	/**
	 * Does it return NULL for an AOP proxy not being in the identity map and
	 * not having FLOW3_Persistence_Entity_UUID?
	 *
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getIdentifierByObjectReturnsNullForUnknownObjectBeingAOPProxy() {
		$unknownObject = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$unknownObject->expects($this->at(0))->method('FLOW3_AOP_Proxy_hasProperty')->with('FLOW3_Persistence_Entity_UUID')->will($this->returnValue(FALSE));
		$unknownObject->expects($this->at(1))->method('FLOW3_AOP_Proxy_hasProperty')->with('FLOW3_Persistence_ValueObject_Hash')->will($this->returnValue(FALSE));
		$unknownObject->expects($this->never())->method('FLOW3_AOP_Proxy_getProperty');
		$mockPersistenceSession = $this->getMock('F3\FLOW3\Persistence\Session');
		$mockPersistenceSession->expects($this->once())->method('hasObject')->with($unknownObject)->will($this->returnValue(FALSE));

		$backend = $this->getMockForAbstractClass($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\AbstractBackend'));
		$backend->injectPersistenceSession($mockPersistenceSession);

		$this->assertNull($backend->_call('getIdentifierByObject', $unknownObject));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function checkTypeSkipsCheckForNullValue() {
		$backend = $this->getMockForAbstractClass($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\AbstractBackend'));
			// would throw an exception if it would check the type...
		$backend->_call('checkType', 'cannotBeValid', NULL);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function typesAndInvalidValuesForCheckType() {
		return array(
			array('string', 1),
			array('string', array()),
			array('string', new \stdClass()),
			array('string', FALSE),
			array('array', 'foo'),
			array('array', 1),
			array('array', TRUE),
			array('DateTime', 1),
			array('DateTime', ''),
			array('DateTime', FALSE),
			array('DateTime', new \stdClass()),
		);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Persistence\Exception\UnexpectedTypeException
	 * @dataProvider typesAndInvalidValuesForCheckType
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function checkTypeThrowsExceptionOnMismatch($type, $value) {
		$backend = $this->getMockForAbstractClass($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\AbstractBackend'));
		$backend->_call('checkType', $type, $value);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function typesAndValidValuesForCheckType() {
		return array(
			array('string', ''),
			array('string', 'foo'),
			array('boolean', FALSE),
			array('array', array()),
			array('DateTime', new \DateTime),
			array('stdClass', new \stdClass()),
		);
	}

	/**
	 * @test
	 * @dataProvider typesAndValidValuesForCheckType
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function checkTypeReturnsOnMatch($type, $value) {
		$backend = $this->getMockForAbstractClass($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\AbstractBackend'));
		$backend->_call('checkType', $type, $value);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getTypeNormalizesDoubleToFloat() {
		$backend = $this->getMockForAbstractClass($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\AbstractBackend'));
		$this->assertEquals('float', $backend->_call('getType', 1.234));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getTypeReturnsClassNameForObjects() {
		$backend = $this->getMockForAbstractClass($this->buildAccessibleProxy('F3\FLOW3\Persistence\Backend\AbstractBackend'));
		$this->assertEquals('stdClass', $backend->_call('getType', new \stdClass()));
	}

}

?>