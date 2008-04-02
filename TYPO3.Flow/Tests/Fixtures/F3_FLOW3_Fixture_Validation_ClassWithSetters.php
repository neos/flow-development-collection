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
 * A dummy class with setters for testing data mapping 
 * 
 * @package		FLOW3
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Fixture_Validation_ClassWithSetters {

	public $property1;
	
	public $property2;
	
	public $property3;

	public $property4;
	
	public function setProperty1($value) {
		$this->property1 = $value;
	}

	public function setProperty3($value) {
		$this->property3 = $value;
	}

	protected function setProperty4($value) {
		$this->property4 = $value;
	}
}
?>