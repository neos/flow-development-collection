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
class PyStringNode {

	/**
	 * @var string
	 */
	protected $rawString;

	/**
	 * @param $rawString The raw string as written in the behat feature file
	 */
	public function __construct($rawString) {
		$this->rawString = $rawString;
	}

	/**
	 * @return string The raw string as written in the behat feature file
	 */
	public function getRaw() {
		return $this->rawString;
	}
}
