<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * @version $Id: TransientRegistryTest.php 1838 2009-02-02 13:03:59Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */

/**
 * Testcase for the session object registry
 *
 * @version $Id: TransientRegistryTest.php 1838 2009-02-02 13:03:59Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SessionRegistryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Object\Exception\InvalidObject
	 */
	public function putObjectThrowsAnExceptionOnInvalidObjects() {
		$sessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array('dummy'), array(), '', FALSE);
		$sessionRegistry->putObject('someClassName', 'no object');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Object\Exception\InvalidObjectName
	 */
	public function putObjectThrowsAnExceptionOnInvalidObjectName() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {}');
		$mockObject = $this->getMock($className);

		$sessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array('dummy'), array(), '', FALSE);
		$sessionRegistry->putObject('', $mockObject);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function putObjectStoresTheGivenObjectUnderItsNameInMemory() {
		$className1 = uniqid('DummyClass');
		eval('class ' . $className1 . ' {}');
		$className2 = uniqid('DummyClass');
		eval('class ' . $className2 . ' {}');
		$mockObject1 = $this->getMock($className1);
		$mockObject2 = $this->getMock($className2);

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$sessionRegistry->putObject($className1, $mockObject1);
		$sessionRegistry->putObject($className2, $mockObject2);

		$expectedArray = array(
			$className1 => $mockObject1,
			$className2 => $mockObject2,
		);

		$this->assertEquals($expectedArray, $sessionRegistry->_get('objects'), 'Objects were not stored correctly in memory.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function writeDataToSessionSerializesTheRegisteredObjectsWithTheObjectSerializerAndStoresTheResultInTheSession() {
		$className1 = uniqid('DummyClass');
		eval('class ' . $className1 . ' {}');
		$className2 = uniqid('DummyClass');
		eval('class ' . $className2 . ' {}');
		$mockObject1 = $this->getMock($className1);
		$mockObject2 = $this->getMock($className2);

		$objects = array($className1 => $mockObject1, $className2 => $mockObject2);

		$mockObjectSerializer = $this->getMock('F3\FLOW3\Object\ObjectSerializer', array(), array(), '', FALSE);
		$mockObjectSerializer->expects($this->at(0))->method('serializeObjectAsPropertyArray')->with($className1, $mockObject1)->will($this->returnValue(array('serialized object1')));
		$mockObjectSerializer->expects($this->at(1))->method('serializeObjectAsPropertyArray')->with($className2, $mockObject2)->will($this->returnValue(array('serialized object2')));

		$serializedObjectsArray = array('serialized object1', 'serialized object2');

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('putData')->with('F3_FLOW3_Object_SessionRegistry', $serializedObjectsArray);

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('storeObjectAsPropertyArray'), array(), '');
		$sessionRegistry->injectSession($mockSession);
		$sessionRegistry->injectObjectSerializer($mockObjectSerializer);
		$sessionRegistry->_set('objects', $objects);

		$sessionRegistry->writeDataToSession();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removeObjectReallyRemovesTheObjectFromStorage() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {}');
		$mockObject = $this->getMock($className);

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$sessionRegistry->putObject($className, $mockObject);
		$sessionRegistry->removeObject($className);

		$cachedObjects = $sessionRegistry->_get('objects');
		$this->assertFalse(isset($cachedObjects[$className]), 'removeObject() did not really remove the object.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Object\Exception\InvalidObjectName
	 */
	public function removeObjectThrowsAnExceptionIfTheObjectDoesntExist() {
		$sessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array('dummy'), array(), '', FALSE);

		$sessionRegistry->removeObject(uniqid('DummyClass'));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function objectExistsReturnsCorrectResult() {
		$className = uniqid('DummyClass');
		eval('class ' . $className . ' {}');
		$mockObject = $this->getMock($className);

		$sessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array('dummy'), array(), '', FALSE);

		$this->assertFalse($sessionRegistry->objectExists($className), 'objectExists() did not return FALSE although the object should not exist yet.');
		$sessionRegistry->putObject($className, $mockObject);
		$this->assertTrue($sessionRegistry->objectExists($className), 'objectExists() did not return TRUE although the object should exist.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException F3\FLOW3\Object\Exception\InvalidObjectName
	 */
	public function getObjectThrowsAnExceptionIfTheObjectDoesntExist() {
		$objectName = uniqid('DummyClass');

		$sessionRegistry = $this->getMock('F3\FLOW3\Object\SessionRegistry', array('objectExists', 'initialize'), array(), '', FALSE);
		$sessionRegistry->expects($this->once())->method('objectExists')->with($objectName)->will($this->returnValue(FALSE));

		$sessionRegistry->getObject($objectName);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getObjectReturnsTheCorrectObject() {
		$objectName = uniqid('DummyClass');
		$object = new \stdClass();

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('objectExists', 'initialize'), array(), '', FALSE);
		$sessionRegistry->expects($this->once())->method('objectExists')->with($objectName)->will($this->returnValue(TRUE));
		$sessionRegistry->_set('objects', array($objectName => $object));

		$this->assertSame($object, $sessionRegistry->getObject($objectName));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeDeserializesTheObjectsArrayFromTheSessionWithTheObjectSerializer() {
		$objectsArray = array(1,2,3,4,5,6);
		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface', array(), array(), '', FALSE);
		$mockSession->expects($this->once())->method('getData')->with('F3_FLOW3_Object_SessionRegistry')->will($this->returnValue($objectsArray));
		$mockSession->expects($this->once())->method('hasKey')->with('F3_FLOW3_Object_SessionRegistry')->will($this->returnValue(TRUE));

		$mockObjectSerializer = $this->getMock('F3\FLOW3\Object\ObjectSerializer', array(), array(), '', FALSE);
		$mockObjectSerializer->expects($this->once())->method('deserializeObjectsArray')->with($objectsArray)->will($this->returnValue(array('many', 'deserialized', 'objects')));

		$sessionRegistry = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\SessionRegistry'), array('dummy'), array(), '', FALSE);
		$sessionRegistry->injectSession($mockSession);
		$sessionRegistry->injectObjectSerializer($mockObjectSerializer);

		$sessionRegistry->initialize();

		$this->assertEquals(array('many', 'deserialized', 'objects'), $sessionRegistry->_get('objects'), 'The object have not been deserialized correctly.');

	}
}
?>
