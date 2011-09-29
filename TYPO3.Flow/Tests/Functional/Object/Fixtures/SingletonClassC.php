<?php
namespace TYPO3\FLOW3\Tests\Functional\Object\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A class of scope singleton
 *
 * @scope singleton
 */
class SingletonClassC {

	/**
	 * @var string
	 */
	public $requiredArgument;

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Object\Fixtures\InterfaceA
	 */
	public $interfaceAImplementation;

	/**
	 * @var string
	 */
	public $settingsArgument;

	/**
	 * @param string $requiredArgument
	 * @param \TYPO3\FLOW3\Tests\Functional\Object\Fixtures\InterfaceA $interfaceAImplementation
	 * @param string $settingsArgument
	 * @param boolean $optionalArgument
	 */
	public function __construct($requiredArgument, InterfaceA $interfaceAImplementation, $settingsArgument, $optionalArgument = FALSE) {
		$this->requiredArgument = $requiredArgument;
		$this->interfaceAImplementation = $interfaceAImplementation;
		$this->settingsArgument = $settingsArgument;
		$this->optionalArgument = $optionalArgument;
	}

}
?>