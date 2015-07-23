<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

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
 * Class CommonObject
 * Representation of an object handled as "\Doctrine\DBAL\Types\Type::OBJECT"
 *
 * @package TYPO3\Flow\Tests\Functional\Persistence\Fixtures
 */
class CommonObject {

	/**
	 * @var string
	 */
	protected $foo;

	/**
	 * @param string $foo
	 * @return $this
	 */
	public function setFoo($foo = NULL) {
		$this->foo = $foo;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFoo() {
		return $this->foo;
	}
}
