<?php
namespace TYPO3\Eel\Tests\Unit\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Eel".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Eel\Context;
use Eel\Evaluator;

/**
 * Test fixture object
 */
class TestObject {

	/**
	 * @var string
	 */
	protected $property;

	/**
	 * @var boolean
	 */
	protected $booleanProperty;


	/**
	 * @return string
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * @param $property
	 */
	public function setProperty($property) {
		$this->property = $property;
	}

	/**
	 * @param boolean $booleanProperty
	 */
	public function setBooleanProperty($booleanProperty) {
		$this->booleanProperty = $booleanProperty;
	}

	/**
	 * @return boolean
	 */
	public function isBooleanProperty() {
		return $this->booleanProperty;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function callMe($name) {
		return 'Hello, ' . $name . '!';
	}

}
?>