<?php
namespace Neos\Flow\Tests\Unit\Property\TypeConverter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\MediaTypeConverter;
use Neos\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MediaTypeConverter
 */
class MediaTypeConverterTest extends UnitTestCase
{
    /**
     * @var MediaTypeConverter
     */
    protected $mediaTypeConverter;

    /**
     * @var PropertyMappingConfigurationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPropertyMappingConfiguration;

    /**
     * Set up this test case
     */
    public function setUp()
    {
        $this->mediaTypeConverter = new MediaTypeConverter();

        $this->mockPropertyMappingConfiguration = $this->getMockBuilder(PropertyMappingConfigurationInterface::class)->getMock();
    }

    /**
     * @test
     */
    public function convertExpectsJsonAsDefault()
    {
        $actualResult = $this->mediaTypeConverter->convertFrom('{"jsonArgument":"jsonValue"}', 'array');
        $expectedResult = ['jsonArgument' => 'jsonValue'];
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function convertReturnsEmptyArrayIfBodyCantBeParsed()
    {
        $actualResult = $this->mediaTypeConverter->convertFrom('<root><xmlArgument>xmlValue</xmlArgument></root>', 'array');
        $expectedResult = [];
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function convertReturnsEmptyArrayIfGivenMediaTypeIsInvalid()
    {
        $this->mockPropertyMappingConfiguration->expects($this->atLeastOnce())->method('getConfigurationValue')->with(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE)->will($this->returnValue('someInvalidMediaType'));

        $actualResult = $this->mediaTypeConverter->convertFrom('{"jsonArgument":"jsonValue"}', 'array', [], $this->mockPropertyMappingConfiguration);
        $expectedResult = [];
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * Data provider
     */
    public function contentTypesBodiesAndExpectedUnifiedArguments()
    {
        return [
            ['application/json', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']],
            ['application/json', 'invalid json source code', []],
            ['application/json; charset=UTF-8', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']],
            ['application/xml', '<root><xmlArgument>xmlValue</xmlArgument></root>', ['xmlArgument' => 'xmlValue']],
            ['text/xml', '<root><xmlArgument>xmlValue</xmlArgument><![CDATA[<!-- text/xml is, by the way, meant to be readable by "the casual user" -->]]></root>', ['xmlArgument' => 'xmlValue']],
            ['text/xml', '<invalid xml source code>', []],
            ['application/xml;charset=UTF8', '<root><xmlArgument>xmlValue</xmlArgument></root>', ['xmlArgument' => 'xmlValue']],

            // the following media types are wrong (not registered at IANA), but still used by some out there:

            ['application/x-javascript', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']],
            ['text/javascript', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']],
            ['text/x-javascript', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']],
            ['text/x-json', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']],
        ];
    }

    /**
     * @test
     * @dataProvider contentTypesBodiesAndExpectedUnifiedArguments
     */
    public function convertTests($mediaType, $requestBody, array $expectedResult)
    {
        $this->mockPropertyMappingConfiguration->expects($this->atLeastOnce())->method('getConfigurationValue')->with(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE)->will($this->returnValue($mediaType));

        $actualResult = $this->mediaTypeConverter->convertFrom($requestBody, 'array', [], $this->mockPropertyMappingConfiguration);
        $this->assertSame($expectedResult, $actualResult);
    }
}
