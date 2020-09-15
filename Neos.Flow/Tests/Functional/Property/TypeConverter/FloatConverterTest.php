<?php
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

use Neos\Flow\I18n\Locale;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\FloatConverter;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Error\Messages\Error as FlowError;

/**
 * Testcase for the Float converter
 *
 */
class FloatConverterTest extends FunctionalTestCase
{
    /**
     * @var FloatConverter
     */
    protected $converter;

    public function setUp()
    {
        parent::setUp();
        $this->converter = $this->objectManager->get(\Neos\Flow\Property\TypeConverter\FloatConverter::class);
    }

    /**
     * @return array Signature: string $locale, string $source, float $expectedResult
     */
    public function localeParsingDataProvider()
    {
        return [
            ['de', '13,20', 13.2],
            ['de', '112,777', 112.777],
            ['de', '10.423,58', 10423.58],

            ['en', '14.42', 14.42],
            ['en', '10,423.58', 10423.58],
            ['en', '10,42358', 1042358],
        ];
    }

    /**
     * @test
     * @dataProvider localeParsingDataProvider
     *
     * @param Locale|string $locale
     * @param $source
     * @param $expectedResult
     */
    public function convertFromUsingVariousLocalesParsesFloatCorrectly($locale, $source, $expectedResult)
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(FloatConverter::class, 'locale', $locale);

        $actualResult = $this->converter->convertFrom($source, 'float', [], $configuration);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function convertFromReturnsErrorIfFormatIsInvalid()
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(FloatConverter::class, 'locale', 'de');

        $actualResult = $this->converter->convertFrom('12,777777', 'float', [], $configuration);
        $this->assertInstanceOf(FlowError::class, $actualResult);

        $this->assertInstanceOf(FlowError::class, $this->converter->convertFrom('84,00', 'float'));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException
     */
    public function convertFromThrowsExceptionIfLocaleIsInvalid()
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(FloatConverter::class, 'locale', 'some-non-existent-locale-identifier');

        $this->converter->convertFrom('84,42', 'float', [], $configuration);
    }

    /**
     * @test
     */
    public function convertFromDoesntUseLocaleParserIfNoConfigurationGiven()
    {
        $this->assertEquals(84, $this->converter->convertFrom('84.000', 'float'));
        $this->assertEquals(84.42, $this->converter->convertFrom('84.42', 'float'));
    }
}
