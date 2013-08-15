<?php
namespace TYPO3\Flow\Tests\Reflection\Fixture;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Fixture class with getters and setters
 *
 */
class DummyClassWithGettersAndSetters {

	protected $property;
	protected $anotherProperty;
	protected $property2;
	protected $booleanProperty = TRUE;
	protected $anotherBooleanProperty = FALSE;

	protected $protectedProperty;

	protected $unexposedProperty = 'unexposed';

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

	public function setAnotherBooleanProperty($anotherBooleanProperty) {
		$this->anotherBooleanProperty = $anotherBooleanProperty;
	}

	public function hasAnotherBooleanProperty() {
		return $this->anotherBooleanProperty;
	}

	protected function getPrivateProperty() {
		return '21';
	}

	public function setWriteOnlyMagicProperty($value) {
	}
}
