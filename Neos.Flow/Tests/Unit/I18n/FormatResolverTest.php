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
use Neos\Flow\I18n;
use Neos\Flow\I18n\Exception\IndexOutOfBoundsException;
use Neos\Flow\I18n\Exception\InvalidFormatPlaceholderException;
use Neos\Flow\I18n\Exception\InvalidFormatterException;
use Neos\Flow\I18n\Exception\UnknownFormatterException;
use Neos\Flow\I18n\FormatResolver;
use Neos\Flow\I18n\Formatter\FormatterInterface;
use Neos\Flow\I18n\Formatter\NumberFormatter;
use Neos\Flow\I18n\Locale;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the FormatResolver
 */
final class FormatResolverTest extends UnitTestCase
{
    /**
     * @var I18n\Locale
     */
    protected $sampleLocale;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->sampleLocale = new Locale('en_GB');
    }

    #[Test]
    public function placeholdersAreResolvedCorrectly(): void
    {
        $mockNumberFormatter = $this->createMock(NumberFormatter::class);
        $matcher = $this->exactly(2);
        $mockNumberFormatter->expects($matcher)->method('format')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame(1, $parameters[0]);
                $this->assertSame($this->sampleLocale, $parameters[1]);
                return '1.0';
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame(2, $parameters[0]);
                $this->assertSame($this->sampleLocale, $parameters[1]);
                $this->assertSame(['percent'], $parameters[2]);
                return '200%';
            }
        });

        /** @var MockObject|I18n\FormatResolver $formatResolver */
        $formatResolver = $this->getAccessibleMock(FormatResolver::class, ['getFormatter']);
        $formatResolver->expects($this->exactly(2))->method('getFormatter')->with('number')->willReturn(($mockNumberFormatter));

        $result = $formatResolver->resolvePlaceholders('Foo {0,number}, bar {1,number,percent}', [1, 2], $this->sampleLocale);
        self::assertEquals('Foo 1.0, bar 200%', $result);

        $result = $formatResolver->resolvePlaceHolders('Foo {0}{1} Bar', ['{', '}'], $this->sampleLocale);
        self::assertEquals('Foo {} Bar', $result);
    }

    #[Test]
    public function returnsStringCastedArgumentWhenFormatterNameIsNotSet(): void
    {
        $formatResolver = new FormatResolver();
        $result = $formatResolver->resolvePlaceholders('{0}', [123], $this->sampleLocale);
        self::assertEquals('123', $result);
    }

    #[Test]
    public function throwsExceptionWhenInvalidPlaceholderEncountered(): void
    {
        $this->expectException(InvalidFormatPlaceholderException::class);
        $formatResolver = new FormatResolver();
        $formatResolver->resolvePlaceholders('{0,damaged {1}', [], $this->sampleLocale);
    }

    #[Test]
    public function throwsExceptionWhenInsufficientNumberOfArgumentsProvided(): void
    {
        $this->expectException(IndexOutOfBoundsException::class);
        $formatResolver = new FormatResolver();
        $formatResolver->resolvePlaceholders('{0}', [], $this->sampleLocale);
    }

    #[Test]
    public function throwsExceptionWhenFormatterDoesNotExist(): void
    {
        $this->expectException(UnknownFormatterException::class);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $matcher = $this->exactly(2);
        $mockObjectManager->expects($matcher)
            ->method('isRegistered')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('foo', $parameters[0]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame('Neos\Flow\I18n\Formatter\FooFormatter', $parameters[0]);
                }
                return false;
            });

        $formatResolver = new FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);

        $formatResolver->resolvePlaceholders('{0,foo}', [123], $this->sampleLocale);
    }

    #[Test]
    public function throwsExceptionWhenFormatterDoesNotImplementFormatterInterface(): void
    {
        $this->expectException(InvalidFormatterException::class);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager
            ->expects($this->once())
            ->method('isRegistered')
            ->with('Acme\Foobar\Formatter\SampleFormatter')
            ->willReturn((true));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService
            ->expects($this->once())
            ->method('isClassImplementationOf')
            ->with('Acme\Foobar\Formatter\SampleFormatter', FormatterInterface::class)
            ->willReturn((false));

        $formatResolver = new FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);
        $this->inject($formatResolver, 'reflectionService', $mockReflectionService);
        $formatResolver->resolvePlaceholders('{0,Acme\Foobar\Formatter\SampleFormatter}', [123], $this->sampleLocale);
    }

    #[Test]
    public function fullyQualifiedFormatterIsCorrectlyBeingUsed(): void
    {
        $mockFormatter = $this->createMock(FormatterInterface::class);
        $mockFormatter->expects($this->once())
            ->method('format')
            ->with(123, $this->sampleLocale, [])
            ->willReturn(('FormatterOutput42'));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager
            ->expects($this->once())
            ->method('isRegistered')
            ->with('Acme\Foobar\Formatter\SampleFormatter')
            ->willReturn((true));
        $mockObjectManager
            ->expects($this->once())
            ->method('get')
            ->with('Acme\Foobar\Formatter\SampleFormatter')
            ->willReturn(($mockFormatter));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService
            ->expects($this->once())
            ->method('isClassImplementationOf')
            ->with('Acme\Foobar\Formatter\SampleFormatter', FormatterInterface::class)
            ->willReturn((true));

        $formatResolver = new FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);
        $this->inject($formatResolver, 'reflectionService', $mockReflectionService);
        $actual = $formatResolver->resolvePlaceholders('{0,Acme\Foobar\Formatter\SampleFormatter}', [123], $this->sampleLocale);
        self::assertEquals('FormatterOutput42', $actual);
    }

    #[Test]
    public function fullyQualifiedFormatterWithLowercaseVendorNameIsCorrectlyBeingUsed(): void
    {
        $mockFormatter = $this->createMock(FormatterInterface::class);
        $mockFormatter->expects($this->once())
            ->method('format')
            ->with(123, $this->sampleLocale, [])
            ->willReturn(('FormatterOutput42'));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager
            ->expects($this->once())
            ->method('isRegistered')
            ->with('acme\Foo\SampleFormatter')
            ->willReturn((true));
        $mockObjectManager
            ->expects($this->once())
            ->method('get')
            ->with('acme\Foo\SampleFormatter')
            ->willReturn(($mockFormatter));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService
            ->expects($this->once())
            ->method('isClassImplementationOf')
            ->with('acme\Foo\SampleFormatter', FormatterInterface::class)
            ->willReturn((true));

        $formatResolver = new FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);
        $this->inject($formatResolver, 'reflectionService', $mockReflectionService);
        $actual = $formatResolver->resolvePlaceholders('{0,acme\Foo\SampleFormatter}', [123], $this->sampleLocale);
        self::assertEquals('FormatterOutput42', $actual);
    }

    #[Test]
    public function namedPlaceholdersAreResolvedCorrectly(): void
    {
        $formatResolver = $this->getMockBuilder(FormatResolver::class)->onlyMethods([])->getMock();

        $result = $formatResolver->resolvePlaceholders('Key {keyName} is {valueName}', ['keyName' => 'foo', 'valueName' => 'bar'], $this->sampleLocale);
        self::assertEquals('Key foo is bar', $result);
    }
}
