<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Property\TypeConverter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Error as FlowError;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\FloatConverter;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Float converter
 *
 */
final class FloatConverterTest extends FunctionalTestCase
{
    /**
     * @var FloatConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = $this->objectManager->get(FloatConverter::class);
    }

    /**
     * @return \Iterator<(int | string), mixed> Signature: string $locale, string $source, float $expectedResult
     */
    public static function localeParsingDataProvider(): \Iterator
    {
        yield ['de', '13,20', 13.2];
        yield ['de', '112,777', 112.777];
        yield ['de', '10.423,58', 10423.58];
        yield ['en', '14.42', 14.42];
        yield ['en', '10,423.58', 10423.58];
        yield ['en', '10,42358', (float)1042358];
    }

    #[DataProvider('localeParsingDataProvider')]
    #[Test]
    public function convertFromUsingVariousLocalesParsesFloatCorrectly(string $locale, string $source, float $expectedResult): void
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(FloatConverter::class, 'locale', $locale);

        $actualResult = $this->converter->convertFrom($source, 'float', [], $configuration);
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function convertFromReturnsErrorIfFormatIsInvalid(): void
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(FloatConverter::class, 'locale', 'de');

        $actualResult = $this->converter->convertFrom('12,777777', 'float', [], $configuration);
        self::assertInstanceOf(FlowError::class, $actualResult);

        self::assertInstanceOf(FlowError::class, $this->converter->convertFrom('84,00', 'float'));
    }

    #[Test]
    public function convertFromThrowsExceptionIfLocaleIsInvalid(): void
    {
        $this->expectException(InvalidLocaleIdentifierException::class);
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(FloatConverter::class, 'locale', 'some-non-existent-locale-identifier');

        $this->converter->convertFrom('84,42', 'float', [], $configuration);
    }

    #[Test]
    public function convertFromDoesntUseLocaleParserIfNoConfigurationGiven(): void
    {
        self::assertEquals(84, $this->converter->convertFrom('84.000', 'float'));
        self::assertEquals(84.42, $this->converter->convertFrom('84.42', 'float'));
    }
}
