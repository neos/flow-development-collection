<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Filter;

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
 * @version $Id$
 */

/**
 * Testcase for the validator resolver
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class ChainTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addingFilterssToAValidatorChainWorks() {
		$filterChain = new \F3\FLOW3\Validation\Filter\Chain();
		$filterObject = $this->getMock('F3\FLOW3\Validation\FilterInterface');

		$index = $filterChain->addFilter($filterObject);

		$this->assertEquals($filterObject, $filterChain->getFilter($index));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function allFiltersInTheChainAreInvocedCorrectly() {
		$filterChain = new \F3\FLOW3\Validation\Filter\Chain();
		$filterObject = $this->getMock('F3\FLOW3\Validation\FilterInterface');
		$filterObject->expects($this->once())->method('filter');
		$secondFilterObject = $this->getMock('F3\FLOW3\Validation\FilterInterface');
		$secondFilterObject->expects($this->once())->method('filter');

		$filterChain->addFilter($filterObject);
		$filterChain->addFilter($secondFilterObject);

		$filterChain->filter('some subject', new \F3\FLOW3\Validation\Errors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Validation\Exception\InvalidChainIndex
	 */
	public function removingAFilterOfTheFilterChainWorks() {
		$filterChain = new \F3\FLOW3\Validation\Filter\Chain();
		$filterObject = $this->getMock('F3\FLOW3\Validation\FilterInterface');
		$secondFilterObject = $this->getMock('F3\FLOW3\Validation\FilterInterface');
		$filterChain->addFilter($filterObject);
		$index = $filterChain->addFilter($secondFilterObject);

		$filterChain->removeFilter($index);

		$filterChain->getFilter($index);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Validation\Exception\InvalidChainIndex
	 */
	public function accessingANotExistingFilterIndexThrowsException() {
		$filterChain = new \F3\FLOW3\Validation\Filter\Chain();

		$filterChain->getFilter(100);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Validation\Exception\InvalidChainIndex
	 */
	public function removingANotExistingFilterIndexThrowsException() {
		$filterChain = new \F3\FLOW3\Validation\Filter\Chain();

		$filterChain->removeFilter(100);
	}
}

?>