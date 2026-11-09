<?php

declare(strict_types=1);

namespace Neos\Kickstarter\Tests\Unit\Service;

use Neos\Flow\Tests\UnitTestCase;
use Neos\Kickstarter\Service\GeneratorService;
use PHPUnit\Framework\Attributes\Test;

/*
 * This file is part of the Neos.Kickstarter package.
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
final class GeneratorServiceTest extends UnitTestCase
{
    #[Test]
    public function normalizeFieldDefinitionsConvertsBoolTypeToBoolean()
    {
        $service = $this->getAccessibleMock(GeneratorService::class);
        $fieldDefinitions = array(
            'field' => array(
                'type' => 'bool'
            )
        );
        $normalizedFieldDefinitions = $service->_call('normalizeFieldDefinitions', $fieldDefinitions);
        self::assertEquals('boolean', $normalizedFieldDefinitions['field']['type']);
    }

    #[Test]
    public function normalizeFieldDefinitionsPrefixesGlobalClassesWithBackslash()
    {
        $service = $this->getAccessibleMock(GeneratorService::class);
        $fieldDefinitions = array(
            'field' => array(
                'type' => 'DateTime'
            )
        );
        $normalizedFieldDefinitions = $service->_call('normalizeFieldDefinitions', $fieldDefinitions);
        self::assertEquals('\DateTime', $normalizedFieldDefinitions['field']['type']);
    }

    #[Test]
    public function normalizeFieldDefinitionsPrefixesLocalTypesWithNamespaceIfNeeded()
    {
        $uniqueClassName = uniqid('Class');
        $service = $this->getAccessibleMock(GeneratorService::class);
        $fieldDefinitions = array(
            'field' => array(
                'type' => $uniqueClassName
            )
        );
        $normalizedFieldDefinitions = $service->_call('normalizeFieldDefinitions', $fieldDefinitions, 'TYPO3\Testing\Domain\Model');
        self::assertEquals('\TYPO3\Testing\Domain\Model\\' . $uniqueClassName, $normalizedFieldDefinitions['field']['type']);
    }
}
