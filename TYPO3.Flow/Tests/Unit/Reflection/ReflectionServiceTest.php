<?php
namespace TYPO3\Flow\Tests\Unit\Reflection;

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
 * Testcase for PropertyReflection
 *
 */
class ReflectionServiceTest extends \TYPO3\Flow\Tests\UnitTestCase {


	/**
	 * @test
	 */
	public function fileWithNoClassAreMarkedUnconfigurable() {
		$reflectionService = $this->getAccessibleMock('TYPO3\Flow\Reflection\ReflectionService', NULL);
		$reflectionService->_call('reflectClass', 'TYPO3\Flow\Tests\Reflection\Fixture\FileWithNoClass');
		$this->assertTrue($reflectionService->isClassUnconfigurable('TYPO3\Flow\Tests\Reflection\Fixture\FileWithNoClass'));

	}

}
?>