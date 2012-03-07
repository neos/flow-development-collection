<?php
namespace TYPO3\FLOW3\Tests\Object\Fixture;

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
 */
class ClassWithSetterAndPropertyInjection {

	/**
	 * @var \TYPO3\Foo\Bar
	 * @FLOW3\Inject
	 */
	protected $firstDependency;

	/**
	 * @var \TYPO3\Coffee\Bar
	 * @FLOW3\Inject
	 */
	protected $secondDependency;

	/**
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	public function injectFirstDependency(\TYPO3\FLOW3\Object\ObjectManagerInterface $firstDependency) {
		$this->firstDependency = $firstDependency;
	}

}
?>