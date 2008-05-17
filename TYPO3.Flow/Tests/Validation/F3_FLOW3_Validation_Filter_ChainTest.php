<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Validation_Filter_ChainTest.php 688 2008-04-03 09:35:36Z andi $
 */

/**
 * Testcase for the validator resolver
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Validation_Filter_ChainTest.php 688 2008-04-03 09:35:36Z andi $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_Filter_ChainTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addingFilterssToAValidatorChainWorks() {
		$filterChain = new F3_FLOW3_Validation_Filter_Chain();
		$filterObject = $this->getMock('F3_FLOW3_Validation_FilterInterface');

		$index = $filterChain->addFilter($filterObject);

		$this->assertEquals($filterObject, $filterChain->getFilter($index));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function allFiltersInTheChainAreInvocedCorrectly() {
		$filterChain = new F3_FLOW3_Validation_Filter_Chain();
		$filterObject = $this->getMock('F3_FLOW3_Validation_FilterInterface');
		$filterObject->expects($this->once())->method('filter');
		$secondFilterObject = $this->getMock('F3_FLOW3_Validation_FilterInterface');
		$secondFilterObject->expects($this->once())->method('filter');

		$filterChain->addFilter($filterObject);
		$filterChain->addFilter($secondFilterObject);

		$filterChain->filter('some subject', new F3_FLOW3_Validation_Errors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removingAFilterOfTheFilterChainWorks() {
		$filterChain = new F3_FLOW3_Validation_Filter_Chain();
		$filterObject = $this->getMock('F3_FLOW3_Validation_FilterInterface');
		$secondFilterObject = $this->getMock('F3_FLOW3_Validation_FilterInterface');
		$filterChain->addFilter($filterObject);
		$index = $filterChain->addFilter($secondFilterObject);

		$filterChain->removeFilter($index);

		try {
			$filterChain->getFilter($index);
			$this->fail('The filter chain did not remove the filter with the given index.');
		} catch(F3_FLOW3_Validation_Exception_InvalidChainIndex $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function accessingANotExistingFilterIndexThrowsException() {
		$filterChain = new F3_FLOW3_Validation_Filter_Chain();

		try {
			$filterChain->getFilter(100);
			$this->fail('The filter chain did throw an error on accessing an invalid filter index.');
		} catch(F3_FLOW3_Validation_Exception_InvalidChainIndex $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removingANotExistingFilterIndexThrowsException() {
		$filterChain = new F3_FLOW3_Validation_Filter_Chain();

		try {
			$filterChain->removeFilter(100);
			$this->fail('The filter chain did throw an error on removing an invalid filter index.');
		} catch(F3_FLOW3_Validation_Exception_InvalidChainIndex $exception) {

		}
	}
}

?>