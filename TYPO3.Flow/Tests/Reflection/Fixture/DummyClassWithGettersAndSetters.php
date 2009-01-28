<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Reflection\Fixture;

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
 * @version $Id:$
 */

/**
 * [Enter description here]
 *
 * @package
 * @subpackage
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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