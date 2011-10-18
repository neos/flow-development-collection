<?php
namespace TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A fixture for testing class schema building
 *
 * @FLOW3\Entity
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
	 * @FLOW3\Validate("$name", type="foo1")
	 * @FLOW3\Validate("$name", type="foo2")
	 * @FLOW3\SkipCsrfProtection
	 */
	public function setName($name) {
		$this->name = $name;
	}

}
?>