<?php
namespace TYPO3\FLOW3\Tests\AOP\Fixture;

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
 * Methods tagged with something
 *
 */
class MethodsTaggedWithSomething {

	/**
	 * Some method
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @someMethod
	 */
	public function someMethod() {
	}

	/**
	 * Some other method
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @someOtherMethod
	 */
	public function someOtherMethod() {
	}

	/**
	 * Something completely different
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @completelyDifferent
	 */
	public function somethingCompletelyDifferent() {
	}
}
?>