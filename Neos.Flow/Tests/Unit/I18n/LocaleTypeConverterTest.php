<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\I18n;

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
use Neos\Flow\I18n\LocaleTypeConverter;
use Neos\Flow\Property\TypeConverterInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Locale type converter
 */
#[CoversClass('\Neos\Flow\I18n\LocaleTypeConverter<extended>::class')]
final class LocaleTypeConverterTest extends UnitTestCase
{
    /**
     * @var TypeConverterInterface
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new LocaleTypeConverter();
    }

    #[Test]
    public function checkMetadata()
    {
        self::assertEquals(['string'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals(Locale::class, $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    #[Test]
    public function convertFromShouldReturnLocale()
    {
        self::assertInstanceOf(Locale::class, $this->converter->convertFrom('de', 'irrelevant'));
    }

    #[Test]
    public function canConvertFromShouldReturnTrue()
    {
        self::assertTrue($this->converter->canConvertFrom('de', Locale::class));
    }

    #[Test]
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        self::assertEmpty($this->converter->getSourceChildPropertiesToBeConverted('something'));
    }
}
