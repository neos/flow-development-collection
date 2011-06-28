<?php
namespace TYPO3\FLOW3\Tests\Unit\Persistence\Aspect;

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
 * Testcase for the PersistenceMagicAspect
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PersistenceMagicAspectTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cloneObjectMarksTheObjectAsCloned() {
		$object = new \stdClass();
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));

		$aspect = new \TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicAspect();
		$aspect->cloneObject($mockJoinPoint);
		$this->assertTrue($object->FLOW3_Persistence_clone);
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

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->atLeastOnce())->method('getProxy')->will($this->returnValue($object));
		$mockJoinPoint->expects($this->atLeastOnce())->method('getMethodArguments')->will($this->returnValue($methodArguments));

		$aspect = new \TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicAspect();
		$aspect->generateValueHash($mockJoinPoint);
		$this->assertEquals(sha1('uuidhash'), $object->FLOW3_Persistence_Identifier);
	}
}

?>