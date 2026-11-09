<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the MediaTypeConverter
 */
final class MediaTypeConverterTest extends UnitTestCase
{
    /**
     * @var MediaTypeConverter
     */
    protected $mediaTypeConverter;

    /**
     * @var PropertyMappingConfigurationInterface|MockObject
     */
    protected $mockPropertyMappingConfiguration;

    /**
     * Set up this test case
     */
    protected function setUp(): void
    {
        $this->mediaTypeConverter = new MediaTypeConverter();

        $this->mockPropertyMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
    }

    #[Test]
    public function convertExpectsJsonAsDefault()
    {
        $actualResult = $this->mediaTypeConverter->convertFrom('{"jsonArgument":"jsonValue"}', 'array');
        $expectedResult = ['jsonArgument' => 'jsonValue'];
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function convertReturnsEmptyArrayIfBodyCantBeParsed()
    {
        $actualResult = $this->mediaTypeConverter->convertFrom('<root><xmlArgument>xmlValue</xmlArgument></root>', 'array');
        $expectedResult = [];
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function convertReturnsEmptyArrayIfGivenMediaTypeIsInvalid()
    {
        $this->mockPropertyMappingConfiguration->expects($this->atLeastOnce())->method('getConfigurationValue')->with(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE)->willReturn(('someInvalidMediaType'));

        $actualResult = $this->mediaTypeConverter->convertFrom('{"jsonArgument":"jsonValue"}', 'array', [], $this->mockPropertyMappingConfiguration);
        $expectedResult = [];
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * Data provider
     */
    public static function contentTypesBodiesAndExpectedUnifiedArguments(): \Iterator
    {
        yield ['application/json', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']];
        yield ['application/json', 'invalid json source code', []];
        yield ['application/json; charset=UTF-8', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']];
        yield ['application/xml', '<root><xmlArgument>xmlValue</xmlArgument></root>', ['xmlArgument' => 'xmlValue']];
        yield ['text/xml', '<root><xmlArgument>xmlValue</xmlArgument><![CDATA[<!-- text/xml is, by the way, meant to be readable by "the casual user" -->]]></root>', ['xmlArgument' => 'xmlValue']];
        yield ['text/xml', '<invalid xml source code>', []];
        yield ['application/xml;charset=UTF8', '<root><xmlArgument>xmlValue</xmlArgument></root>', ['xmlArgument' => 'xmlValue']];
        // the following media types are wrong (not registered at IANA), but still used by some out there:
        yield ['application/x-javascript', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']];
        yield ['text/javascript', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']];
        yield ['text/x-javascript', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']];
        yield ['text/x-json', '{"jsonArgument":"jsonValue"}', ['jsonArgument' => 'jsonValue']];
    }

    #[DataProvider('contentTypesBodiesAndExpectedUnifiedArguments')]
    #[Test]
    public function convertTests($mediaType, $requestBody, array $expectedResult)
    {
        $this->mockPropertyMappingConfiguration->expects($this->atLeastOnce())->method('getConfigurationValue')->with(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE)->willReturn(($mediaType));

        $actualResult = $this->mediaTypeConverter->convertFrom($requestBody, 'array', [], $this->mockPropertyMappingConfiguration);
        self::assertSame($expectedResult, $actualResult);
    }
}
