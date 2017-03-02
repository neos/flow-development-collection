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
    public function setUp()
    {
        $this->sampleLocale = new I18n\Locale('en_GB');
    }

    /**
     * @test
     */
    public function placeholdersAreResolvedCorrectly()
    {
        $mockNumberFormatter = $this->createMock(I18n\Formatter\NumberFormatter::class);
        $mockNumberFormatter->expects($this->at(0))->method('format')->with(1, $this->sampleLocale)->will($this->returnValue('1.0'));
        $mockNumberFormatter->expects($this->at(1))->method('format')->with(2, $this->sampleLocale, ['percent'])->will($this->returnValue('200%'));

        $formatResolver = $this->getAccessibleMock(I18n\FormatResolver::class, ['getFormatter']);
        $formatResolver->expects($this->exactly(2))->method('getFormatter')->with('number')->will($this->returnValue($mockNumberFormatter));

        $result = $formatResolver->resolvePlaceholders('Foo {0,number}, bar {1,number,percent}', [1, 2], $this->sampleLocale);
        $this->assertEquals('Foo 1.0, bar 200%', $result);

        $result = $formatResolver->resolvePlaceHolders('Foo {0}{1} Bar', ['{', '}'], $this->sampleLocale);
        $this->assertEquals('Foo {} Bar', $result);
    }

    /**
     * @test
     */
    public function returnsStringCastedArgumentWhenFormatterNameIsNotSet()
    {
        $formatResolver = new I18n\FormatResolver();
        $result = $formatResolver->resolvePlaceholders('{0}', [123], $this->sampleLocale);
        $this->assertEquals('123', $result);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\I18n\Exception\InvalidFormatPlaceholderException
     */
    public function throwsExceptionWhenInvalidPlaceholderEncountered()
    {
        $formatResolver = new I18n\FormatResolver();
        $formatResolver->resolvePlaceholders('{0,damaged {1}', [], $this->sampleLocale);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\I18n\Exception\IndexOutOfBoundsException
     */
    public function throwsExceptionWhenInsufficientNumberOfArgumentsProvided()
    {
        $formatResolver = new I18n\FormatResolver();
        $formatResolver->resolvePlaceholders('{0}', [], $this->sampleLocale);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\I18n\Exception\UnknownFormatterException
     */
    public function throwsExceptionWhenFormatterDoesNotExist()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager
            ->expects($this->at(0))
            ->method('isRegistered')
            ->with('foo')
            ->will($this->returnValue(false));
        $mockObjectManager
            ->expects($this->at(1))
            ->method('isRegistered')
            ->with('Neos\Flow\I18n\Formatter\FooFormatter')
            ->will($this->returnValue(false));

        $formatResolver = new I18n\FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);

        $formatResolver->resolvePlaceholders('{0,foo}', [123], $this->sampleLocale);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\I18n\Exception\InvalidFormatterException
     */
    public function throwsExceptionWhenFormatterDoesNotImplementFormatterInterface()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager
            ->expects($this->once())
            ->method('isRegistered')
            ->with('Acme\Foobar\Formatter\SampleFormatter')
            ->will($this->returnValue(true));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService
            ->expects($this->once())
            ->method('isClassImplementationOf')
            ->with('Acme\Foobar\Formatter\SampleFormatter', I18n\Formatter\FormatterInterface::class)
            ->will($this->returnValue(false));

        $formatResolver = new I18n\FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);
        $this->inject($formatResolver, 'reflectionService', $mockReflectionService);
        $formatResolver->resolvePlaceholders('{0,Acme\Foobar\Formatter\SampleFormatter}', [123], $this->sampleLocale);
    }

    /**
     * @test
     */
    public function fullyQualifiedFormatterIsCorrectlyBeingUsed()
    {
        $mockFormatter = $this->createMock(I18n\Formatter\FormatterInterface::class);
        $mockFormatter->expects($this->once())
            ->method('format')
            ->with(123, $this->sampleLocale, [])
            ->will($this->returnValue('FormatterOutput42'));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager
            ->expects($this->once())
            ->method('isRegistered')
            ->with('Acme\Foobar\Formatter\SampleFormatter')
            ->will($this->returnValue(true));
        $mockObjectManager
            ->expects($this->once())
            ->method('get')
            ->with('Acme\Foobar\Formatter\SampleFormatter')
            ->will($this->returnValue($mockFormatter));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService
            ->expects($this->once())
            ->method('isClassImplementationOf')
            ->with('Acme\Foobar\Formatter\SampleFormatter', I18n\Formatter\FormatterInterface::class)
            ->will($this->returnValue(true));

        $formatResolver = new I18n\FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);
        $this->inject($formatResolver, 'reflectionService', $mockReflectionService);
        $actual = $formatResolver->resolvePlaceholders('{0,Acme\Foobar\Formatter\SampleFormatter}', [123], $this->sampleLocale);
        $this->assertEquals('FormatterOutput42', $actual);
    }

    /**
     * @test
     */
    public function fullyQualifiedFormatterWithLowercaseVendorNameIsCorrectlyBeingUsed()
    {
        $mockFormatter = $this->createMock(I18n\Formatter\FormatterInterface::class);
        $mockFormatter->expects($this->once())
            ->method('format')
            ->with(123, $this->sampleLocale, [])
            ->will($this->returnValue('FormatterOutput42'));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager
            ->expects($this->once())
            ->method('isRegistered')
            ->with('acme\Foo\SampleFormatter')
            ->will($this->returnValue(true));
        $mockObjectManager
            ->expects($this->once())
            ->method('get')
            ->with('acme\Foo\SampleFormatter')
            ->will($this->returnValue($mockFormatter));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService
            ->expects($this->once())
            ->method('isClassImplementationOf')
            ->with('acme\Foo\SampleFormatter', I18n\Formatter\FormatterInterface::class)
            ->will($this->returnValue(true));

        $formatResolver = new I18n\FormatResolver();
        $formatResolver->injectObjectManager($mockObjectManager);
        $this->inject($formatResolver, 'reflectionService', $mockReflectionService);
        $actual = $formatResolver->resolvePlaceholders('{0,acme\Foo\SampleFormatter}', [123], $this->sampleLocale);
        $this->assertEquals('FormatterOutput42', $actual);
    }

    /**
     * @test
     */
    public function namedPlaceholdersAreResolvedCorrectly()
    {
        $formatResolver = $this->getMockBuilder(I18n\FormatResolver::class)->setMethods(['dummy'])->getMock();

        $result = $formatResolver->resolvePlaceholders('Key {keyName} is {valueName}', ['keyName' => 'foo', 'valueName' => 'bar'], $this->sampleLocale);
        $this->assertEquals('Key foo is bar', $result);
    }
}
