<?php
namespace TYPO3\FLOW3\Tests\Unit\Persistence\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
class PersistenceMagicAspectTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @return void
	 */
	public function cloneObjectMarksTheObjectAsCloned() {
		$object = new \stdClass();
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));

		$aspect = new \TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicAspect();
		$aspect->cloneObject($mockJoinPoint);
		$this->assertTrue($object->FLOW3_Persistence_clone);
	}

	/**
	 * @test
	 * @return void
	 */
	public function generateUuidGeneratesUuidAndRegistersProxyAsNewObject() {
		$className = 'Class' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $className . ' implements \TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicInterface { public $FLOW3_Persistence_Identifier = NULL; }');
		$object = new $className();

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getProxy')->will($this->returnValue($object));

		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->atLeastOnce())->method('registerNewObject')->with($object);

		$aspect = $this->getAccessibleMock('TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicAspect', array('dummy'), array());
		$aspect->_set('persistenceManager', $mockPersistenceManager);
		$aspect->generateUuid($mockJoinPoint);

		$this->assertEquals(36, strlen($object->FLOW3_Persistence_Identifier));
	}


	/**
	 * @test
	 */
	public function generateValueHashUsesIdentifierSubObjects() {
		$subObject1 = new \stdClass();
		$subObject1->FLOW3_Persistence_Identifier = 'uuid';
		$subObject2 = new \stdClass();
		$subObject2->FLOW3_Persistence_Identifier = 'hash';

		$methodArguments = array(
			'foo' => $subObject1,
			'bar' => $subObject2
		);

		$className = 'Class' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $className . ' { public $foo; public $bar; }');
		$object = new $className();

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodArguments')->will($this->returnValue($methodArguments));

		$aspect = new \TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicAspect();
		$aspect->generateValueHash($mockJoinPoint);
		$this->assertEquals(sha1($className . 'uuidhash'), $object->FLOW3_Persistence_Identifier);
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
		$object->FLOW3_Persistence_Identifier = 'existinguuidhash';

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodArguments')->will($this->returnValue($methodArguments));

		$aspect = new \TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicAspect();
		$aspect->generateValueHash($mockJoinPoint);
		$this->assertEquals(sha1($className . 'existinguuidhash' . 'bar'), $object->FLOW3_Persistence_Identifier);
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

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodArguments')->will($this->returnValue($methodArguments));

		$aspect = new \TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicAspect();
		$aspect->generateValueHash($mockJoinPoint);
		$this->assertEquals(sha1($className . $date->getTimestamp()), $object->FLOW3_Persistence_Identifier);
	}

}
?>