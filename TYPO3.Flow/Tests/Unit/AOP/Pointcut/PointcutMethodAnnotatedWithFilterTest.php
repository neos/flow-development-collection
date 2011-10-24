<?php
namespace TYPO3\FLOW3\Tests\Unit\AOP\Pointcut;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once (FLOW3_PATH_FLOW3 . 'Tests/Unit/AOP/Fixtures/MethodsTaggedWithSomething.php');

/**
 * Testcase for the Pointcut Method-Annotated-With Filter
 *
 */
class PointcutMethodAnnotatedWithFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenAnnotation() {
		$className = 'TYPO3\FLOW3\Tests\AOP\Fixture\MethodsTaggedWithSomething';

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'saveToCache', 'hasMethod'));
		$mockReflectionService->injectClassLoader(new \TYPO3\FLOW3\Core\ClassLoader());
		$mockReflectionService->expects($this->any())->method('hasMethod')->will($this->returnValue(TRUE));
		$mockReflectionService->initializeObject();

		$methodAnnotatedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodAnnotatedWithFilter('TYPO3\FLOW3\Annotations\Session');
		$methodAnnotatedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($methodAnnotatedWithFilter->matches(__CLASS__, 'someMethod', $className, 1));

		$methodAnnotatedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutMethodAnnotatedWithFilter('Acme\Annotation\Does\Not\Exist');
		$methodAnnotatedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertFalse($methodAnnotatedWithFilter->matches(__CLASS__, 'somethingCompletelyDifferent', $className, 1));
	}
}
?>