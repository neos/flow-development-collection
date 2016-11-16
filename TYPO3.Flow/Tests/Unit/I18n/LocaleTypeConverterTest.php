<?php
namespace TYPO3\Flow\Tests\Unit\I18n;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\I18n;
use TYPO3\Flow\I18n\LocaleTypeConverter;
use TYPO3\Flow\Property\TypeConverterInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Locale type converter
 *
 * @covers I18n\LocaleTypeConverter<extended>
 */
class LocaleTypeConverterTest extends UnitTestCase
{
    /**
     * @var TypeConverterInterface
     */
    protected $converter;

    public function setUp()
    {
        $this->converter = new LocaleTypeConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['string'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals(I18n\Locale::class, $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromShouldReturnLocale()
    {
        $this->assertInstanceOf(I18n\Locale::class, $this->converter->convertFrom('de', 'irrelevant'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrue()
    {
        $this->assertTrue($this->converter->canConvertFrom('de', I18n\Locale::class));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        $this->assertEmpty($this->converter->getSourceChildPropertiesToBeConverted('something'));
    }
}
