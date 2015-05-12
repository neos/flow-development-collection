<?php
namespace TYPO3\Flow\Tests\Functional\Object;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * Functional tests for the Object configuration via Objects.yaml
 */
class ConfigurationTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * See the configuration in Testing/Objects.yaml
	 * @test
	 */
	public function configuredObjectDWillGetAssignedObjectFWithCorrectlyConfiguredConstructorValue() {
		$instance = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassD');
		/** @var $instanceE Fixtures\PrototypeClassE */
		$instanceE = ObjectAccess::getProperty($instance, 'objectE', TRUE);
		$this->assertEquals('The constructor set value', $instanceE->getNullValue());

	}
}
