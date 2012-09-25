<?php
namespace TYPO3\Flow\Tests\Unit\Persistence\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the PersistenceMagicAspect
 *
 */
class PersistenceMagicAspectTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 * @return void
	 */
	public function cloneObjectMarksTheObjectAsCloned() {
		$object = new \stdClass();
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));

		$aspect = new \TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect();
		$aspect->cloneObject($mockJoinPoint);
		$this->assertTrue($object->Flow_Persistence_clone);
	}

	/**
	 * @test
	 * @return void
	 */
	public function generateUuidGeneratesUuidAndRegistersProxyAsNewObject() {
		$className = 'Class' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $className . ' implements \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface { public $Persistence_Object_Identifier = NULL; }');
		$object = new $className();

		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getProxy')->will($this->returnValue($object));

		$mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->atLeastOnce())->method('registerNewObject')->with($object);

		$aspect = $this->getAccessibleMock('TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect', array('dummy'), array());
		$aspect->_set('persistenceManager', $mockPersistenceManager);
		$aspect->generateUuid($mockJoinPoint);

		$this->assertEquals(36, strlen($object->Persistence_Object_Identifier));
	}


	/**
	 * @test
	 */
	public function generateValueHashUsesIdentifierSubObjects() {
		$subObject1 = new \stdClass();
		$subObject1->Persistence_Object_Identifier = 'uuid';
		$subObject2 = new \stdClass();
		$subObject2->Persistence_Object_Identifier = 'hash';

		$methodArguments = array(
			'foo' => $subObject1,
			'bar' => $subObject2
		);

		$className = 'Class' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $className . ' { public $foo; public $bar; }');
		$object = new $className();

		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodArguments')->will($this->returnValue($methodArguments));

		$aspect = new \TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect();
		$aspect->generateValueHash($mockJoinPoint);
		$this->assertEquals(sha1($className . 'uuidhash'), $object->Persistence_Object_Identifier);
	}

	/**
	 * @test
	 */
	public function generateValueHashUsesExistingPersistenceIdentifierForNestedConstructorCalls() {
		$methodArguments = array(
			'foo' => 'bar'
		);

		$className = 'Class' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $className . ' { public $foo; public $bar; }');
		$object = new $className();
		$object->Persistence_Object_Identifier = 'existinguuidhash';

		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodArguments')->will($this->returnValue($methodArguments));

		$aspect = new \TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect();
		$aspect->generateValueHash($mockJoinPoint);
		$this->assertEquals(sha1($className . 'existinguuidhash' . 'bar'), $object->Persistence_Object_Identifier);
	}

	/**
	 * @test
	 */
	public function generateValueHashUsesTimestampOfDateTime() {
		$date = new \DateTime();
		$methodArguments = array(
			'foo' => new \DateTime()
		);

		$className = 'Class' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $className . ' { }');
		$object = new $className();

		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodArguments')->will($this->returnValue($methodArguments));

		$aspect = new \TYPO3\Flow\Persistence\Aspect\PersistenceMagicAspect();
		$aspect->generateValueHash($mockJoinPoint);
		$this->assertEquals(sha1($className . $date->getTimestamp()), $object->Persistence_Object_Identifier);
	}

}
?>