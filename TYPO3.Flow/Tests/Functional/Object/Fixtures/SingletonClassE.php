<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

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
 * A class of scope singleton
 *
 * @Flow\Scope("singleton")
 */
class SingletonClassE {

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB
	 */
	protected $objectB;

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB $objectB
	 * @return void
	 */
	public function __construct(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB $objectB) {
		$this->objectB = $objectB;
	}

}
?>