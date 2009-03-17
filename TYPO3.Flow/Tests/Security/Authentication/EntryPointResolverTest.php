<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication;

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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: RequestPatternResolverTest.php 1886 2009-02-09 16:08:54Z robert $
 */

/**
 * Testcase for the authentication entry point resolver
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: RequestPatternResolverTest.php 1886 2009-02-09 16:08:54Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class EntryPointResolverTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Security\Exception\NoEntryPointFound
	 */
	public function resolveEntryPointClassThrowsAnExceptionIfNoEntryPointIsAvailable() {
		$entryPointResolver = new \F3\FLOW3\Security\Authentication\EntryPointResolver($this->objectManager);

		$entryPointResolver->resolveEntryPointClass('IfSomeoneCreatesAClassNamedLikeThisTheFailingOfThisTestIsHisLeastProblem');
		$this->fail('No exception was thrown.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveRequestPatternReturnsTheCorrectRequestPatternForAShortName() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\Manager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('ValidShortName')->will($this->returnValue(''));
		$mockObjectManager->expects($this->at(1))->method('getCaseSensitiveObjectName')->with('F3\FLOW3\Security\Authentication\EntryPoint\ValidShortName')->will($this->returnValue('F3\FLOW3\Security\Authentication\EntryPoint\ValidShortName'));

		$entryPointResolver = new \F3\FLOW3\Security\Authentication\EntryPointResolver($mockObjectManager);
		$entryPointClass = $entryPointResolver->resolveEntryPointClass('ValidShortName');

		$this->assertEquals('F3\FLOW3\Security\Authentication\EntryPoint\ValidShortName', $entryPointClass, 'The wrong classname has been resolved');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveRequestPatternReturnsTheCorrectRequestPatternForACompleteClassName() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\Manager', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('ExistingEntryPointClass')->will($this->returnValue('ExistingEntryPointClass'));

		$entryPointResolver = new \F3\FLOW3\Security\Authentication\EntryPointResolver($mockObjectManager);
		$entryPointClass = $entryPointResolver->resolveEntryPointClass('ExistingEntryPointClass');

		$this->assertEquals('ExistingEntryPointClass', $entryPointClass, 'The wrong classname has been resolved');
	}
}
?>