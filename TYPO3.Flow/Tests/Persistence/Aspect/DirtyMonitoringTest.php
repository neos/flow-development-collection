<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Aspect;

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
 * Testcase for the Dirty Monitoring Aspect
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DirtyMonitoringTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function memorizeCleanStateWithoutArgumentHandlesAllProperties() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array('SomeClass'));
		$mockClassSchema->expects($this->any())->method('getProperties')->will($this->returnValue(array('foo' => 1, 'bar' => 1)));
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoring();
		$aspect->injectPersistenceManager($mockPersistenceManager);

		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->at(0))->method('FLOW3_AOP_Proxy_getProperty')->with('foo');
		$object->expects($this->at(1))->method('FLOW3_AOP_Proxy_getProperty')->with('foo');
		$object->expects($this->at(2))->method('FLOW3_AOP_Proxy_getProperty')->with('bar');
		$object->expects($this->at(3))->method('FLOW3_AOP_Proxy_getProperty')->with('bar');
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($object));

		$aspect->memorizeCleanState($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function memorizeCleanStateWithArgumentHandlesSpecifiedProperty() {
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoring();

		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->exactly(2))->method('FLOW3_AOP_Proxy_getProperty')->with('foo')->will($this->returnValue('bar'));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->exactly(2))->method('getMethodArgument')->will($this->returnValue('foo'));

		$aspect->memorizeCleanState($mockJoinPoint);
		$this->assertEquals($object->FLOW3_Persistence_cleanProperties['foo'], 'bar');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function memorizeCleanStateClonesObjects() {
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoring();

		$value = new \stdClass();
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->exactly(2))->method('FLOW3_AOP_Proxy_getProperty')->with('foo')->will($this->returnValue($value));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->exactly(2))->method('getMethodArgument')->will($this->returnValue('foo'));

		$aspect->memorizeCleanState($mockJoinPoint);
		$this->assertEquals($object->FLOW3_Persistence_cleanProperties['foo'], $value);
		$this->assertNotSame($object->FLOW3_Persistence_cleanProperties['foo'], $value);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyDetectsChangesInLiterals() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array('SomeClass'));
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoring();
		$aspect->injectPersistenceManager($mockPersistenceManager);
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->with('foo')->will($this->returnValue('bar'));
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnValue('foo'));

		$object->FLOW3_Persistence_cleanProperties = array('foo' => 'bar');
		$this->assertFalse($aspect->isDirty($mockJoinPoint));

		$object->FLOW3_Persistence_cleanProperties = array('foo' => 'baz');
		$this->assertTrue($aspect->isDirty($mockJoinPoint));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isDirtyDetectsChangesInObjects() {
		$mockClassSchema = $this->getMock('F3\FLOW3\Persistence\ClassSchema', array(), array('SomeClass'));
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getClassSchema')->will($this->returnValue($mockClassSchema));
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoring();
		$aspect->injectPersistenceManager($mockPersistenceManager);
		$value = new \stdClass();
		$valueClone = clone $value;
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$object->expects($this->any())->method('FLOW3_AOP_Proxy_getProperty')->with('foo')->will($this->returnValue($value));
		$object->FLOW3_Persistence_cleanProperties = array('foo' => $valueClone);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnValue('foo'));

		$this->assertFalse($aspect->isDirty($mockJoinPoint));

		$valueClone->someChange = TRUE;
		$this->assertTrue($aspect->isDirty($mockJoinPoint));
	}

	/**
	 * @test
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cloneObjectUnsetsTheCleanPropertiesArrayAtTheClonedObject() {
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoring();

		$object = new \stdClass();
		$object->FLOW3_Persistence_cleanProperties = array('foo');

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($object));

		$aspect->cloneObject($mockJoinPoint);
		$this->assertFalse(isset($object->FLOW3_Persistence_cleanProperties));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function newObjectsAreDirty() {
		$aspect = new \F3\FLOW3\Persistence\Aspect\DirtyMonitoring();
		$object = $this->getMock('F3\FLOW3\AOP\ProxyInterface');
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));

		$this->assertTrue($aspect->isDirty($mockJoinPoint));
	}

}

?>