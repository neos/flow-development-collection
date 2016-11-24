<?php
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

use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Mvc;

/**
 * Testcase for the MVC Property Mapping Configuration Service
 */
class MvcPropertyMappingConfigurationServiceTest extends UnitTestCase
{
    /**
     * Data provider for generating the list of trusted properties
     *
     * @return array
     */
    public function dataProviderForgenerateTrustedPropertiesToken()
    {
        return [
            'Simple Case - Empty' => [
                [],
                [],
            ],
            'Simple Case - Single Value' => [
                ['field1'],
                ['field1' => 1],
            ],
            'Simple Case - Two Values' => [
                ['field1', 'field2'],
                [
                    'field1' => 1,
                    'field2' => 1
                ],
            ],
            'Recursion' => [
                ['field1', 'field[subfield1]', 'field[subfield2]'],
                [
                    'field1' => 1,
                    'field' => [
                        'subfield1' => 1,
                        'subfield2' => 1
                    ]
                ],
            ],
            'recursion with duplicated field name' => [
                ['field1', 'field[subfield1]', 'field[subfield2]', 'field1'],
                [
                    'field1' => 1,
                    'field' => [
                        'subfield1' => 1,
                        'subfield2' => 1
                    ]
                ],
            ],
            'Recursion with un-named fields at the end (...[]). There, they should be made explicit by increasing the counter' => [
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
            ],
        ];
    }

    /**
     * Data Provider for invalid values in generating the list of trusted properties,
     * which should result in an exception
     *
     * @return array
     */
    public function dataProviderForgenerateTrustedPropertiesTokenWithUnallowedValues()
    {
        return [
            'Overriding form fields (string overridden by array) - 1' => [
                ['field1', 'field2', 'field2[bla]', 'field2[blubb]'],
            ],
            'Overriding form fields (string overridden by array) - 2' => [
                ['field1', 'field2[bla]', 'field2[bla][blubb][blubb]'],
            ],
            'Overriding form fields (array overridden by string) - 1' => [
                ['field1', 'field2[bla]', 'field2[blubb]', 'field2'],
            ],
            'Overriding form fields (array overridden by string) - 2' => [
                ['field1', 'field2[bla][blubb][blubb]', 'field2[bla]'],
            ],
            'Empty [] not as last argument' => [
                ['field1', 'field2[][bla]'],
            ]

        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForgenerateTrustedPropertiesToken
     */
    public function generateTrustedPropertiesTokenGeneratesTheCorrectHashesInNormalOperation($input, $expected)
    {
        $requestHashService = $this->getMockBuilder(Mvc\Controller\MvcPropertyMappingConfigurationService::class)->setMethods(['serializeAndHashFormFieldArray'])->getMock();
        $requestHashService->expects($this->once())->method('serializeAndHashFormFieldArray')->with($expected);
        $requestHashService->generateTrustedPropertiesToken($input);
    }

    /**
     * @test
     * @dataProvider dataProviderForgenerateTrustedPropertiesTokenWithUnallowedValues
     * @expectedException \Neos\Flow\Security\Exception\InvalidArgumentForHashGenerationException
     */
    public function generateTrustedPropertiesTokenThrowsExceptionInWrongCases($input)
    {
        $requestHashService = $this->getMockBuilder(Mvc\Controller\MvcPropertyMappingConfigurationService::class)->setMethods(['serializeAndHashFormFieldArray'])->getMock();
        $requestHashService->generateTrustedPropertiesToken($input);
    }

    /**
     * @test
     */
    public function serializeAndHashFormFieldArrayWorks()
    {
        $formFieldArray = [
            'bla' => [
                'blubb' => 1,
                'hu' => 1
            ]
        ];
        $mockHash = '12345';

        $hashService = $this->getAccessibleMock(Mvc\Controller\MvcPropertyMappingConfigurationService::class, ['appendHmac']);
        $hashService->expects($this->once())->method('appendHmac')->with(serialize($formFieldArray))->will($this->returnValue(serialize($formFieldArray) . $mockHash));

        $requestHashService = $this->getAccessibleMock(Mvc\Controller\MvcPropertyMappingConfigurationService::class, ['dummy']);
        $requestHashService->_set('hashService', $hashService);

        $expected = serialize($formFieldArray) . $mockHash;
        $actual = $requestHashService->_call('serializeAndHashFormFieldArray', $formFieldArray);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationDoesNothingIfTrustedPropertiesAreNotSet()
    {
        $request = $this->getMockBuilder(Mvc\ActionRequest::class)->setMethods(['getInternalArgument'])->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getInternalArgument')->with('__trustedProperties')->will($this->returnValue(null));
        $arguments = new Mvc\Controller\Arguments();

        $requestHashService = new Mvc\Controller\MvcPropertyMappingConfigurationService();
        $requestHashService->initializePropertyMappingConfigurationFromRequest($request, $arguments);

        // dummy assertion to avoid PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationReturnsEarlyIfNoTrustedPropertiesAreSet()
    {
        $trustedProperties = [
            'foo' => 1
        ];
        $this->initializePropertyMappingConfiguration($trustedProperties);
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationReturnsEarlyIfArgumentIsUnknown()
    {
        $trustedProperties = [
            'nonExistingArgument' => 1
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $this->assertFalse($arguments->hasArgument('nonExistingArgument'));
    }

    /**
     * @test
     */
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
        $this->assertTrue($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        $this->assertNull($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));

        $this->assertTrue($propertyMappingConfiguration->forProperty('nested')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        $this->assertNull($propertyMappingConfiguration->forProperty('nested')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertFalse($propertyMappingConfiguration->forProperty('nested')->shouldMap('someProperty'));
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationSetsCreationAllowedIfIdentityPropertyIsNotSet()
    {
        $trustedProperties = [
            'foo' => [
                'bar' => []
            ]
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        $this->assertNull($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        $this->assertTrue($propertyMappingConfiguration->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));

        $this->assertNull($propertyMappingConfiguration->forProperty('bar')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
        $this->assertTrue($propertyMappingConfiguration->forProperty('bar')->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertFalse($propertyMappingConfiguration->forProperty('bar')->shouldMap('someProperty'));
    }

    /**
     * @test
     */
    public function initializePropertyMappingConfigurationSetsAllowedFields()
    {
        $trustedProperties = [
            'foo' => [
                'bar' => 1
            ]
        ];
        $arguments = $this->initializePropertyMappingConfiguration($trustedProperties);
        $propertyMappingConfiguration = $arguments->getArgument('foo')->getPropertyMappingConfiguration();
        $this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
        $this->assertTrue($propertyMappingConfiguration->shouldMap('bar'));
    }

    /**
     * @test
     */
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
        $this->assertFalse($propertyMappingConfiguration->shouldMap('someProperty'));
        $this->assertTrue($propertyMappingConfiguration->shouldMap('bar'));
        $this->assertTrue($propertyMappingConfiguration->forProperty('bar')->shouldMap('foo'));
    }


    /**
     * Helper which initializes the property mapping configuration and returns arguments
     *
     * @param array $trustedProperties
     * @return Mvc\Controller\Arguments
     */
    protected function initializePropertyMappingConfiguration(array $trustedProperties)
    {
        $request = $this->getMockBuilder(Mvc\ActionRequest::class)->setMethods(['getInternalArgument'])->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getInternalArgument')->with('__trustedProperties')->will($this->returnValue('fooTrustedProperties'));
        $arguments = new Mvc\Controller\Arguments();
        $mockHashService = $this->getMockBuilder(HashService::class)->setMethods(['validateAndStripHmac'])->getMock();
        $mockHashService->expects($this->once())->method('validateAndStripHmac')->with('fooTrustedProperties')->will($this->returnValue(serialize($trustedProperties)));

        $arguments->addNewArgument('foo', 'something');
        $this->inject($arguments->getArgument('foo'), 'propertyMappingConfiguration', new PropertyMappingConfiguration());

        $requestHashService = new Mvc\Controller\MvcPropertyMappingConfigurationService();
        $this->inject($requestHashService, 'hashService', $mockHashService);

        $requestHashService->initializePropertyMappingConfigurationFromRequest($request, $arguments);

        return $arguments;
    }
}
