<?php
namespace TYPO3\Flow\Tests\Functional\Property\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A simple entity for PropertyMapper test
 *
 * @Flow\Entity
 */
class TestEntitySubclassWithNewField extends TestEntity {

	/**
	 * @var string
	 */
	protected $testField;

	/**
	 * @param string $testField
	 */
	public function setTestField($testField) {
		$this->testField = $testField;
	}

	/**
	 * @return string
	 */
	public function getTestField() {
		return $this->testField;
	}
}
