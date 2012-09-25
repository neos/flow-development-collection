<?php
namespace TYPO3\Flow\Tests\Reflection\Fixture;

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
 * Proxy of the implementation of dummy interface number 1 for the Reflection tests
 *
 */
class ProxyOfImplementationOfDummyInterface1 extends ImplementationOfDummyInterface1 implements \TYPO3\Flow\Object\Proxy\ProxyInterface {

	/**
	 * A stub to satisfy the Flow Proxy Interface
	 */
	public function __wakeup() {}

}
?>