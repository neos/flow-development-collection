<?php
namespace TYPO3\Flow\Tests\Functional\Object;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * Functional tests for the Object configuration via Objects.yaml
 */
class ConfigurationTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * See the configuration in Testing/Objects.yaml
     * @test
     */
    public function configuredObjectDWillGetAssignedObjectFWithCorrectlyConfiguredConstructorValue()
    {
        $instance = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassD');
        /** @var $instanceE Fixtures\PrototypeClassE */
        $instanceE = ObjectAccess::getProperty($instance, 'objectE', true);
        $this->assertEquals('The constructor set value', $instanceE->getNullValue());
    }
}
