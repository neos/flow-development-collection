<?php
namespace TYPO3\Flow\Tests\Functional\Reflection\Fixtures;

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
 * A fixture for testing class schema building
 *
 * @Flow\Entity
 */
class ClassSchemaFixture {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $things = array();

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Some text with a @param annotation, which should not be parsed.
	 *
	 * @param string $name
	 * @return void
	 * @Flow\Validate("$name", type="foo1")
	 * @Flow\Validate("$name", type="foo2")
	 * @Flow\SkipCsrfProtection
	 */
	public function setName($name) {
		$this->name = $name;
	}

}
?>