<?php
namespace TYPO3\Kickstart\Tests\Unit\Service;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the generator service
 *
 */
class GeneratorServiceTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function normalizeFieldDefinitionsConvertsBoolTypeToBoolean()
    {
        $service = $this->getAccessibleMock(\TYPO3\Kickstart\Service\GeneratorService::class, array('dummy'));
        $fieldDefinitions = array(
            'field' => array(
                'type' => 'bool'
            )
        );
        $normalizedFieldDefinitions = $service->_call('normalizeFieldDefinitions', $fieldDefinitions);
        $this->assertEquals('boolean', $normalizedFieldDefinitions['field']['type']);
    }

    /**
     * @test
     */
    public function normalizeFieldDefinitionsPrefixesGlobalClassesWithBackslash()
    {
        $service = $this->getAccessibleMock(\TYPO3\Kickstart\Service\GeneratorService::class, array('dummy'));
        $fieldDefinitions = array(
            'field' => array(
                'type' => 'DateTime'
            )
        );
        $normalizedFieldDefinitions = $service->_call('normalizeFieldDefinitions', $fieldDefinitions);
        $this->assertEquals('\DateTime', $normalizedFieldDefinitions['field']['type']);
    }

    /**
     * @test
     */
    public function normalizeFieldDefinitionsPrefixesLocalTypesWithNamespaceIfNeeded()
    {
        $uniqueClassName = uniqid('Class');
        $service = $this->getAccessibleMock(\TYPO3\Kickstart\Service\GeneratorService::class, array('dummy'));
        $fieldDefinitions = array(
            'field' => array(
                'type' => $uniqueClassName
            )
        );
        $normalizedFieldDefinitions = $service->_call('normalizeFieldDefinitions', $fieldDefinitions, 'TYPO3\Testing\Domain\Model');
        $this->assertEquals('\TYPO3\Testing\Domain\Model\\' . $uniqueClassName, $normalizedFieldDefinitions['field']['type']);
    }
}
