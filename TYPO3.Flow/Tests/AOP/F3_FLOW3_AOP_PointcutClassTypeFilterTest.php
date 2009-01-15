<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * Testcase for the Pointcut Class Type Filter
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class PointcutClassTypeFilterTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matchesTellsIfTheOneOfTheInterfaceNamesTheClassImplementsMatchesTheGivenRegularExpression() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array('getInterfaceNamesImplementedByClass'), array(), '', FALSE, TRUE);
		$mockReflectionService->expects($this->any())->method('getInterfaceNamesImplementedByClass')->with('Foo')->will($this->returnValue(array('Bar', 'Baz', 'Fu', 'Uta')));

		$filter = new \F3\FLOW3\AOP\PointcutClassTypeFilter('.*ar');
		$filter->injectReflectionService($mockReflectionService);
		$this->assertTrue($filter->matches('Foo', '', '', 1));

		$filter = new \F3\FLOW3\AOP\PointcutClassTypeFilter('Fu');
		$filter->injectReflectionService($mockReflectionService);
		$this->assertTrue($filter->matches('Foo', '', '', 1));

		$filter = new \F3\FLOW3\AOP\PointcutClassTypeFilter('Rob');
		$filter->injectReflectionService($mockReflectionService);
		$this->assertFalse($filter->matches('Foo', '', '', 1));
	}
}
?>