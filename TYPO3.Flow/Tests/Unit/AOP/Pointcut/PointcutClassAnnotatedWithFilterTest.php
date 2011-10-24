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

require_once (FLOW3_PATH_FLOW3 . 'Tests/Unit/AOP/Fixtures/ClassTaggedWithSomething.php');

/**
 * Testcase for the Pointcut Class-Annotated-With Filter
 *
 */
class PointcutClassAnnotatedWithFilterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function matchesTellsIfTheSpecifiedRegularExpressionMatchesTheGivenAnnotation() {
		$className = 'TYPO3\FLOW3\Tests\AOP\Fixture\ClassTaggedWithSomething';

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('loadFromCache', 'saveToCache'), array(), '', FALSE, TRUE);
		$mockReflectionService->injectClassLoader(new \TYPO3\FLOW3\Core\ClassLoader());
		$mockReflectionService->initializeObject();

		$classAnnotatedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassAnnotatedWithFilter('TYPO3\FLOW3\Annotations\Aspect');
		$classAnnotatedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($classAnnotatedWithFilter->matches($className, '', '', 1));

		$classAnnotatedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassAnnotatedWithFilter('TYPO3\FLOW3\Annotations\Scope');
		$classAnnotatedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertTrue($classAnnotatedWithFilter->matches($className, '', '', 1));

		$classAnnotatedWithFilter = new \TYPO3\FLOW3\AOP\Pointcut\PointcutClassAnnotatedWithFilter('Acme\No\Such\Annotation');
		$classAnnotatedWithFilter->injectReflectionService($mockReflectionService);
		$this->assertFalse($classAnnotatedWithFilter->matches($className, '', '', 1));
	}
}
?>