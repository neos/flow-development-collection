<?php
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

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\I18n;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the FormatResolver
 */
class FormatResolverTest extends UnitTestCase
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
        $this->sampleLocale = new I18n\Locale('en_GB');
    }

    /**
     * @test
     */
    public function placeholdersAreResolvedCorrectly(): void
    {
        $matcher = $this->exactly(2);
        $mockNumberFormatter = $this->createMock(I18n\Formatter\NumberFormatter::class);
        $mockNumberFormatter->expects($matcher)->method('format')
            ->willReturnCallback(
                function (mixed $value, I18n\Locale $locale, array $styleProperties = []) use ($matcher): string {
                    if ($matcher->numberOfInvocations() === 1) {
                        $this->assertSame(1, $value);
                        $this->assertSame($this->sampleLocale, $locale);
                        return '1.0';
                    }
                    if ($matcher->numberOfInvocations() === 2) {
                        $this->assertSame(2, $value);
                        $this->assertSame($this->sampleLocale, $locale);
                        $this->assertSame(['percent'], $styleProperties);
                        return '200%';
                    }

                    return 'unexpected invocation';
                }
            );

        /** @var MockObject|I18n\FormatResolver $formatResolver */
        $formatResolver = $this->getAccessibleMock(I18n\FormatResolver::class, ['getFormatter']);
        $formatResolver->expects($this->exactly(2))->method('getFormatter')->with('number')->willReturn(($mockNumberFormatter));

        $result = $formatResolver->resolvePlaceholders('Foo {0,number}, bar {1,number,percent}', [1, 2], $this->sampleLocale);
        self::assertEquals('Foo 1.0, bar 200%', $result);

        $result = $formatResolver->resolvePlaceHolders('Foo {0}{1} Bar', ['{', '}'], $this->sampleLocale);
        self::assertEquals('Foo {} Bar', $result);
    }

    /**
     * @test
     */
    public function returnsStringCastedArgumentWhenFormatterNameIsNotSet(): void
    {
        $formatResolver = new I18n\FormatResolver();
        $result = $formatResolver->resolvePlaceholders('{0}', [123], $this->sampleLocale);
        self::assertEquals('123', $result);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenInvalidPlaceholderEncountered(): void
    {
        $this->expectException(I18n\Exception\InvalidFormatPlaceholderException::class);
        $formatResolver = new I18n\FormatResolver();
        $formatResolver->resolvePlaceholders('{0,damaged {1}', [], $this->sampleLocale);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenInsufficientNumberOfArgumentsProvided(): void
    {
        $this->expectException(I18n\Exception\IndexOutOfBoundsException::class);
        $formatResolver = new I18n\FormatResolver();
        $formatResolver->resolvePlaceholders('{0}', [], $this->sampleLocale);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenFormatterDoesNotExist(): void
    {
        $this->expectException(I18n\Exception\UnknownFormatterException::class);
        $matcher = $this->exactly(2);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager
            ->expects($matcher)
            ->method('isRegistered')
            ->willReturnCallback(
                function (string $objectName) use ($matcher) {
                    if ($matcher->numberOfInvocations() === 1) {
                        $this->assertSame('foo', $objectName);
                    }
                    if ($matcher->numberOfInvocations() === 2) {
                        $this->assertSame('Neos\Flow\I18n\Formatter\FooFormatter', $objectName);
                    }
                }
            )
            ->willReturn(false);

        $formatResolver = new I18n\FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);

        $formatResolver->resolvePlaceholders('{0,foo}', [123], $this->sampleLocale);
    }

    /**
     * @test
     */
    public function throwsExceptionWhenFormatterDoesNotImplementFormatterInterface(): void
    {
        $this->expectException(I18n\Exception\InvalidFormatterException::class);
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
            ->with('Acme\Foobar\Formatter\SampleFormatter', I18n\Formatter\FormatterInterface::class)
            ->willReturn((false));

        $formatResolver = new I18n\FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);
        $this->inject($formatResolver, 'reflectionService', $mockReflectionService);
        $formatResolver->resolvePlaceholders('{0,Acme\Foobar\Formatter\SampleFormatter}', [123], $this->sampleLocale);
    }

    /**
     * @test
     */
    public function fullyQualifiedFormatterIsCorrectlyBeingUsed(): void
    {
        $mockFormatter = $this->createMock(I18n\Formatter\FormatterInterface::class);
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
            ->with('Acme\Foobar\Formatter\SampleFormatter', I18n\Formatter\FormatterInterface::class)
            ->willReturn((true));

        $formatResolver = new I18n\FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);
        $this->inject($formatResolver, 'reflectionService', $mockReflectionService);
        $actual = $formatResolver->resolvePlaceholders('{0,Acme\Foobar\Formatter\SampleFormatter}', [123], $this->sampleLocale);
        self::assertEquals('FormatterOutput42', $actual);
    }

    /**
     * @test
     */
    public function fullyQualifiedFormatterWithLowercaseVendorNameIsCorrectlyBeingUsed(): void
    {
        $mockFormatter = $this->createMock(I18n\Formatter\FormatterInterface::class);
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
            ->with('acme\Foo\SampleFormatter', I18n\Formatter\FormatterInterface::class)
            ->willReturn((true));

        $formatResolver = new I18n\FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);
        $this->inject($formatResolver, 'reflectionService', $mockReflectionService);
        $actual = $formatResolver->resolvePlaceholders('{0,acme\Foo\SampleFormatter}', [123], $this->sampleLocale);
        self::assertEquals('FormatterOutput42', $actual);
    }

    /**
     * @test
     */
    public function namedPlaceholdersAreResolvedCorrectly(): void
    {
        $formatResolver = $this->getMockBuilder(I18n\FormatResolver::class)->onlyMethods([])->getMock();

        $result = $formatResolver->resolvePlaceholders('Key {keyName} is {valueName}', ['keyName' => 'foo', 'valueName' => 'bar'], $this->sampleLocale);
        self::assertEquals('Key foo is bar', $result);
    }
}
