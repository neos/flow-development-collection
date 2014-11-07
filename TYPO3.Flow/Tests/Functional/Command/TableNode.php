<?php
namespace TYPO3\Flow\Tests\Functional\Command;

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
 * A helper class for behat scenario parameters, needed when processing
 * behat scenarios/steps in an isolated process
 */
class TableNode {

	/**
	 * @var string
	 */
	protected $hash;

	/**
	 * @param string $hash The table source hash string
	 */
	public function __construct($hash) {
		$this->hash = $hash;
	}

	/**
	 * @return string The table source hash string
	 */
	public function getHash() {
		return $this->hash;
	}
}
