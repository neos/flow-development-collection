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

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A class of scope prototype (but without explicit scope annotation)
 */
class PrototypeClassD {

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassB
	 */
	protected $objectB;

	/**
	 * @var integer
	 */
	public $injectionRuns = 0;

	/**
	 * @param \TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassB $objectB
	 * @return void
	 */
	public function injectObjectB(\TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassB $objectB) {
		$this->injectionRuns++;
		$this->objectB = $objectB;
	}

	/**
	 *
	 */
	public function __construct() {

	}

}
?>