<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Fixture\Validation;

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
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ClassWithSetters {

	/**
	 * @var mixed
	 */
	public $property1;

	/**
	 * @var mixed
	 */
	protected $property2;

	/**
	 * @var mixed
	 */
	public $property3;

	/**
	 * @var mixed
	 */
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
	
	public function getProperty2() {
		return $this->property2;
	}
}
?>