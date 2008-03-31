<?php
declare(encoding = 'utf-8');

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
 * Testcase for ValidatorChains
 * 
 * @package		FLOW3
 * @version 	$Id: F3_FLOW3_Validation_Validator_ChainTest.php 469 2008-02-06 19:52:45Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Validation_Validator_ChainTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addingValidatorsToAValidatorChainWorks() {
		$validatorChain = new F3_FLOW3_Validation_Validator_Chain();
		$validatorObject = $this->getMock('F3_FLOW3_Validation_ValidatorInterface');
		
		$index = $validatorChain->addValidator($validatorObject);
		
		$this->assertEquals($validatorObject, $validatorChain->getValidator($index));
	}
	
	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addingValidatorsToAValidatorChainWorks() {
		$validatorChain = new F3_FLOW3_Validation_Validator_Chain();
		
		$validatorObject = $this->getMock('F3_FLOW3_Validation_ValidatorInterface');
		$validatorObject->expects($this->once())->method('');
	}
}

?>