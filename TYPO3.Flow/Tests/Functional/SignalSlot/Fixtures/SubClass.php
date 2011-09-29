<?php
namespace TYPO3\FLOW3\Tests\Functional\SignalSlot\Fixtures;

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
 * A concrete class for testing signals in abstract classes
 *
 * @scope prototype
 */
class SubClass extends AbstractClass {

	public $slotWasCalled = FALSE;

	/**
	 * @return void
	 */
	public function triggerSomethingSignalFromSubClass() {
		$this->emitSomething();
	}

	/**
	 * @signal
	 * @return void
	 */
	public function emitSomething() {
	}

	/**
	 * @return void
	 */
	public function somethingSlot() {
		$this->slotWasCalled = TRUE;
	}
}
?>