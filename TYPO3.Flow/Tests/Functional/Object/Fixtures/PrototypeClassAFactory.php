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

/**
 * A factory which creates PrototypeClassA instances
 */
class PrototypeClassAFactory {

	/**
	 * Creates a new instance of PrototypeClassA
	 *
	 * @param string $someProperty
	 * @return \TYPO3\Flow\Tests\Functional\Object\Fixtures\FLOWPrototypeClassA
	 */
	public function create($someProperty) {
		$object = new PrototypeClassA();
		$object->setSomeProperty($someProperty);
		return $object;
	}

}
