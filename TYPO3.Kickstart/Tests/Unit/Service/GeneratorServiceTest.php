<?php
namespace TYPO3\Kickstart\Tests\Unit\Service;

/*
 * This file is part of the TYPO3.Kickstart package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
        $service = $this->getAccessibleMock('TYPO3\Kickstart\Service\GeneratorService', array('dummy'));
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
        $service = $this->getAccessibleMock('TYPO3\Kickstart\Service\GeneratorService', array('dummy'));
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
        $service = $this->getAccessibleMock('TYPO3\Kickstart\Service\GeneratorService', array('dummy'));
        $fieldDefinitions = array(
            'field' => array(
                'type' => $uniqueClassName
            )
        );
        $normalizedFieldDefinitions = $service->_call('normalizeFieldDefinitions', $fieldDefinitions, 'TYPO3\Testing\Domain\Model');
        $this->assertEquals('\TYPO3\Testing\Domain\Model\\' . $uniqueClassName, $normalizedFieldDefinitions['field']['type']);
    }
}
