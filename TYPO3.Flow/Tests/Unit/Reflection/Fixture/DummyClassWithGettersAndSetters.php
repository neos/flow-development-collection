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
 * Fixture class with getters and setters
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DummyClassWithGettersAndSetters {

	protected $property;
	protected $anotherProperty;
	protected $property2;
	protected $booleanProperty = TRUE;

	protected $protectedProperty;

	protected $unexposedProperty;

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

	public function getProperty2() {
		return $this->property2;
	}
	public function setProperty2($property2) {
		$this->property2 = $property2;
	}

	protected function getProtectedProperty() {
		return '42';
	}

	protected function setProtectedProperty($value) {
		$this->protectedProperty = $value;
	}

	public function isBooleanProperty() {
		return 'method called ' . $this->booleanProperty;
	}

	protected function getPrivateProperty() {
		return '21';
	}

	public function setWriteOnlyMagicProperty($value) {
	}
}


?>