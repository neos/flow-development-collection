<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Reflection\Fixture;

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
 * @version $Id:$
 */
/**
 * [Enter description here]
 *
 * @package
 * @subpackage
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DummyClassWithGettersAndSetters {
	protected $property;
	protected $anotherProperty;
	
	protected $protectedProperty;
	
	public $publicProperty;
	public $publicProperty2 = 42;
	
	public function setProperty($property) {
		$this->property = $property;
	}
	
	public function getProperty() {
		return $this->property;
	}
	
	public function setAnotherProperty($anotherProperty) {
		$this->anotherProperty = $anotherProperty;
	}
	
	public function getAnotherProperty() {
		return $this->anotherProperty;
	}
	
	protected function getProtectedProperty() {
		return '42';
	}
	
	protected function setProtectedProperty($bla) {
		
	}
	protected function getPrivateProperty() {
		return '21';
	}
}


?>