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
 * Testcase for the PersistenceMagicAspect
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PersistenceMagicAspectTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cloneObjectMarksTheObjectAsCloned() {
		$aspect = new \F3\FLOW3\Persistence\Aspect\PersistenceMagicAspect();

		$object = new \stdClass();
		$object->FLOW3_Persistence_cleanProperties = array('foo');

		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($object));

		$this->assertFalse($aspect->isClone($mockJoinPoint));
		$aspect->cloneObject($mockJoinPoint);
		$this->assertTrue($aspect->isClone($mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function generateValueHashUsesIdentifierOrHashOfSubObjects() {
		$this->markTestIncomplete();
	}
}

?>