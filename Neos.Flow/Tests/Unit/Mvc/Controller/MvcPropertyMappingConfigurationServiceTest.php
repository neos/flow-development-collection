<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Mvc;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\MvcPropertyMappingConfiguration;
use Neos\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the MVC Property Mapping Configuration Service
 */
final class MvcPropertyMappingConfigurationServiceTest extends UnitTestCase
{
    /**
     * Data provider for generating the list of trusted properties
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function dataProviderForgenerateTrustedPropertiesToken(): \Iterator
    {
        yield 'Simple Case - Empty' => [
            [],
            [],
        ];
        yield 'Simple Case - Single Value' => [
            ['field1'],
            ['field1' => 1],
        ];
        yield 'Simple Case - Two Values' => [
            ['field1', 'field2'],
            [
                'field1' => 1,
                'field2' => 1
            ],
        ];
        yield 'Recursion' => [
            ['field1', 'field[subfield1]', 'field[subfield2]'],
            [
                'field1' => 1,
                'field' => [
                    'subfield1' => 1,
                    'subfield2' => 1
                ]
            ],
        ];
        yield 'recursion with duplicated field name' => [
            ['field1', 'field[subfield1]', 'field[subfield2]', 'field1'],
            [
                'field1' => 1,
                'field' => [
                    'subfield1' => 1,
                    'subfield2' => 1
                ]
            ],
        ];
        yield 'Recursion with un-named fields at the end (...[]). There, they should be made explicit by increasing the counter' => [
            ['field1', 'field[subfield1][]', 'field[subfield1][]', 'field[subfield2]'],
            [
                'field1' => 1,
                'field' => [
                    'subfield1' => [
                        0 => 1,
                        1 => 1
                    ],
                    'subfield2' => 1
                ]
            ],
        ];
    }

    /**
     * Data Provider for invalid values in generating the list of trusted properties,
     * which should result in an exception
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function dataProviderForgenerateTrustedPropertiesTokenWithUnallowedValues(): \Iterator
    {
        yield 'Overriding form fields (string overridden by array) - 1' => [
            ['field1', 'field2', 'field2[bla]', 'field2[blubb]'],
        ];
        yield 'Overriding form fields (string overridden by array) - 2' => [
            ['field1', 'field2[bla]', 'field2[bla][blubb][blubb]'],
        ];
        yield 'Overriding form fields (array overridden by string) - 1' => [
            ['field1', 'field2[bla]', 'field2[blubb]', 'field2'],
        ];
        yield 'Overriding form fields (array overridden by string) - 2' => [
            ['field1', 'field2[bla][blubb][blubb]', 'field2[bla]'],
        ];
        yield 'Empty [] not as last argument' => [
            ['field1', 'field2[][bla]'],
        ];
    }

    #[DataProvider('dataProviderForgenerateTrustedPropertiesToken')]
    #[Test]
    public function generateTrustedPropertiesTokenGeneratesTheCorrectHashesInNormalOperation($input, $expected)
    {
        $requestHashService = $this->getMockBuilder(MvcPropertyMappingConfigurationService::class)->onlyMethods(['serializeAndHashFormFieldArray'])->getMock();
        $requestHashService->expects($this->once())->method('serializeAndHashFormFieldArray')->with($expected);
        $requestHashService->generateTrustedPropertiesToken($input);
    }

    #[DataProvider('dataProviderForgenerateTrustedPropertiesTokenWithUnallowedValues')]
    #[Test]
    public function generateTrustedPropertiesTokenThrowsExceptionInWrongCases($input)
    {
        $this->expectException(InvalidArgumentForHashGenerationException::class);
        $requestHashService = $this->getMockBuilder(MvcPropertyMappingConfigurationService::class)->onlyMethods(['serializeAndHashFormFieldArray'])->getMock();
        $requestHashService->generateTrustedPropertiesToken($input);
    }

    #[Test]
    public function serializeAndHashFormFieldArrayWorks()
    {
        $formFieldArray = [
            'bla' => [
                'blubb' => 1,
                'hu' => 1
            ]
        ];
        $mockHash = '12345';

        $hashService = $this->getAccessibleMock(HashService::class, ['appendHmac']);
        $hashService->expects($this->once())->method('appendHmac')->with(serialize($formFieldArray))->willReturn((serialize($formFieldArray) . $mockHash));

        $requestHashService = $this->getAccessibleMock(MvcPropertyMappingConfigurationService::class, []);
        $requestHashService->_set('hashService', $hashService);

        $expected = serialize($formFieldArray) . $mockHash;
        $actual = $requestHashService->_call('serializeAndHashFormFieldArray', $formFieldArray);
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function initializePropertyMappingConfigurationDoesNothingIfTrustedPropertiesAreNotSet()
    {
        $request = $this->getMockBuilder(ActionRequest::class)->onlyMethods(['getInternalArgument'])->disableOriginalConstructor()->getMock();
        $request->method('getInternalArgument')->with('__trustedProperties')->willReturn((null));
        $arguments = new Arguments();

        $requestHashService = new MvcPropertyMappingConfigurationService();
        $requestHashService->initializePropertyMappingConfigurationFromRequest($request, $arguments);
    }

    #[Test]
    public function initializePropertyMappingConfigurationReturnsEarlyIfNoTrustedPropertiesAreSet()
    {
        $trustedProperties = [
            'foo' => 1
        ];
        $this->initializePropertyMappingConfiguration($trustedProperties);
    }

    #[Test]
    public function initializePropertyMappingConfigurationReturnsEarlyIfArgumentIsUnknown()
    {
        $trustedProperties = [
            'nonExistingArgument' => 1
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        self::assertFalse($arguments->hasArgument('nonExistingArgument'));
    }

    #[Test]
    public function initializePropertyMappingConfigurationSetsModificationAllowedIfIdentityPropertyIsSet()
    {
        $trustedProperties = [
            'foo' => [
                '__identity' => 1,
                'nested' => [
                    '__identity' => 1,
                ]
            ]
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        self::assertTrue($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        self::assertNull($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));

        self::assertTrue($propertyMappingConfiguration->forProperty('nested')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        self::assertNull($propertyMappingConfiguration->forProperty('nested')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertFalse($propertyMappingConfiguration->forProperty('nested')->shouldMap('someProperty'));
    }

    #[Test]
    public function initializePropertyMappingConfigurationSetsCreationAllowedIfIdentityPropertyIsNotSet()
    {
        $trustedProperties = [
            'foo' => [
                'bar' => []
            ]
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        self::assertNull($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        self::assertTrue($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));

        self::assertNull($propertyMappingConfiguration->forProperty('bar')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        self::assertTrue($propertyMappingConfiguration->forProperty('bar')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertFalse($propertyMappingConfiguration->forProperty('bar')->shouldMap('someProperty'));
    }

    #[Test]
    public function initializePropertyMappingConfigurationSetsAllowedFields()
    {
        $trustedProperties = [
            'foo' => [
                'bar' => 1
            ]
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        self::assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
        self::assertTrue($propertyMappingConfiguration->shouldMap('bar'));
    }

    #[Test]
    public function initializePropertyMappingConfigurationSetsAllowedFieldsRecursively()
    {
        $trustedProperties = [
            'foo' => [
                'bar' => [
                    'foo' => 1
                ]
            ]
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        self::assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
        self::assertTrue($propertyMappingConfiguration->shouldMap('bar'));
        self::assertTrue($propertyMappingConfiguration->forProperty('bar')->shouldMap('foo'));
    }

    /**
     * Helper which initializes the property mapping configuration and returns arguments
     *
     * @param array $trustedProperties
     * @return Mvc\Controller\Arguments
     */
    protected function initializePropertyMappingConfiguration(array $trustedProperties)
    {
        $request = $this->getMockBuilder(ActionRequest::class)->onlyMethods(['getInternalArgument'])->disableOriginalConstructor()->getMock();
        $request->method('getInternalArgument')->with('__trustedProperties')->willReturn(('fooTrustedProperties'));
        $arguments = new Arguments();
        $mockHashService = $this->getMockBuilder(HashService::class)->onlyMethods(['validateAndStripHmac'])->getMock();
        $mockHashService->expects($this->once())->method('validateAndStripHmac')->with('fooTrustedProperties')->willReturn((serialize($trustedProperties)));

        $arguments->addNewArgument('foo', 'something');
        $this->inject($arguments->getArgument('foo'), 'propertyMappingConfiguration', new MvcPropertyMappingConfiguration());

        $requestHashService = new MvcPropertyMappingConfigurationService();
        $this->inject($requestHashService, 'hashService', $mockHashService);

        $requestHashService->initializePropertyMappingConfigurationFromRequest($request, $arguments);

        return $arguments;
    }
}
