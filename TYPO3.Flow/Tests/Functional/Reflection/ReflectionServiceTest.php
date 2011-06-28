<?php
namespace TYPO3\FLOW3\Tests\Functional\Reflection;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Functional tests for the Dependency Injection features
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ReflectionServiceTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theReflectionServiceBuildsClassSchemataForEntities() {
		$reflectionService = $this->objectManager->get('TYPO3\FLOW3\Reflection\ReflectionService');
		$classSchema = $reflectionService->getClassSchema('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture');

		$this->assertNotNull($classSchema);
		$this->assertSame('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture', $classSchema->getClassName());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function theReflectionServiceCorrectlyBuildsMethodTagsValues() {
		$reflectionService = $this->objectManager->get('TYPO3\FLOW3\Reflection\ReflectionService');
		$actual = $reflectionService->getMethodTagsValues('TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\ClassSchemaFixture', 'setName');

		$expected = array(
			'param' => array(
				'string $name'
			),
			'return' => array(
				'void'
			),
			'validate' => array(
				'foo1 bar1',
				'foo2 bar2'
			),
			'skipCsrf' => array()
		);
		$this->assertSame($expected, $actual);
	}
}
?>