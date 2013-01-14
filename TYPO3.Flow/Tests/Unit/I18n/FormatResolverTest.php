<?php
namespace TYPO3\Flow\Tests\Unit\I18n;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the FormatResolver
 *
 */
class FormatResolverTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\I18n\Locale
	 */
	protected $sampleLocale;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->sampleLocale = new \TYPO3\Flow\I18n\Locale('en_GB');
	}

	/**
	 * @test
	 */
	public function placeholdersAreResolvedCorrectly() {
		$mockNumberFormatter = $this->getMock('TYPO3\Flow\I18n\Formatter\NumberFormatter');
		$mockNumberFormatter->expects($this->at(0))->method('format')->with(1, $this->sampleLocale)->will($this->returnValue('1.0'));
		$mockNumberFormatter->expects($this->at(1))->method('format')->with(2, $this->sampleLocale, array('percent'))->will($this->returnValue('200%'));

		$formatResolver = $this->getAccessibleMock('TYPO3\Flow\I18n\FormatResolver', array('getFormatter'));
		$formatResolver->expects($this->exactly(2))->method('getFormatter')->with('number')->will($this->returnValue($mockNumberFormatter));

		$result = $formatResolver->resolvePlaceholders('Foo {0,number}, bar {1,number,percent}', array(1, 2), $this->sampleLocale);
		$this->assertEquals('Foo 1.0, bar 200%', $result);
	}

	/**
	 * @test
	 */
	public function returnsStringCastedArgumentWhenFormatterNameIsNotSet() {
		$formatResolver = new \TYPO3\Flow\I18n\FormatResolver();
		$result = $formatResolver->resolvePlaceholders('{0}', array(123), $this->sampleLocale);
		$this->assertEquals('123', $result);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\I18n\Exception\InvalidFormatPlaceholderException
	 */
	public function throwsExceptionWhenInvalidPlaceholderEncountered() {
		$formatResolver = new \TYPO3\Flow\I18n\FormatResolver();
		$formatResolver->resolvePlaceholders('{0,damaged {1}', array(), $this->sampleLocale);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\I18n\Exception\IndexOutOfBoundsException
	 */
	public function throwsExceptionWhenInsufficientNumberOfArgumentsProvided() {
		$formatResolver = new \TYPO3\Flow\I18n\FormatResolver();
		$formatResolver->resolvePlaceholders('{0}', array(), $this->sampleLocale);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\I18n\Exception\UnknownFormatterException
	 */
	public function throwsExceptionWhenFormatterDoesNotExist() {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager
			->expects($this->at(0))
			->method('isRegistered')
			->with('foo')
			->will($this->returnValue(FALSE));
		$mockObjectManager
			->expects($this->at(1))
			->method('isRegistered')
			->with('TYPO3\Flow\I18n\Formatter\FooFormatter')
			->will($this->returnValue(FALSE));

		$formatResolver = new \TYPO3\Flow\I18n\FormatResolver();
		$formatResolver->injectObjectManager($mockObjectManager);

		$formatResolver->resolvePlaceholders('{0,foo}', array(123), $this->sampleLocale);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\I18n\Exception\InvalidFormatterException
	 */
	public function throwsExceptionWhenFormatterDoesNotImplementFormatterInterface() {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager
			->expects($this->once())
			->method('isRegistered')
			->with('Acme\Foobar\Formatter\SampleFormatter')
			->will($this->returnValue(TRUE));

		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$mockReflectionService
			->expects($this->once())
			->method('isClassImplementationOf')
			->with('Acme\Foobar\Formatter\SampleFormatter', 'TYPO3\Flow\I18n\Formatter\FormatterInterface')
			->will($this->returnValue(FALSE));

		$formatResolver = new \TYPO3\Flow\I18n\FormatResolver();
		$formatResolver->injectObjectManager($mockObjectManager);
		$this->inject($formatResolver, 'reflectionService', $mockReflectionService);
		$formatResolver->resolvePlaceholders('{0,Acme\Foobar\Formatter\SampleFormatter}', array(123), $this->sampleLocale);
	}

	/**
	 * @test
	 */
	public function fullyQualifiedFormatterIsCorrectlyBeingUsed() {
		$mockFormatter = $this->getMock('TYPO3\Flow\I18n\Formatter\FormatterInterface');
		$mockFormatter->expects($this->once())
			->method('format')
			->with(123, $this->sampleLocale, array())
			->will($this->returnValue('FormatterOutput42'));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager
			->expects($this->once())
			->method('isRegistered')
			->with('Acme\Foobar\Formatter\SampleFormatter')
			->will($this->returnValue(TRUE));
		$mockObjectManager
			->expects($this->once())
			->method('get')
			->with('Acme\Foobar\Formatter\SampleFormatter')
			->will($this->returnValue($mockFormatter));

		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$mockReflectionService
			->expects($this->once())
			->method('isClassImplementationOf')
			->with('Acme\Foobar\Formatter\SampleFormatter', 'TYPO3\Flow\I18n\Formatter\FormatterInterface')
			->will($this->returnValue(TRUE));

		$formatResolver = new \TYPO3\Flow\I18n\FormatResolver();
		$formatResolver->injectObjectManager($mockObjectManager);
		$this->inject($formatResolver, 'reflectionService', $mockReflectionService);
		$actual = $formatResolver->resolvePlaceholders('{0,Acme\Foobar\Formatter\SampleFormatter}', array(123), $this->sampleLocale);
		$this->assertEquals('FormatterOutput42', $actual);
	}

	/**
	 * @test
	 */
	public function fullyQualifiedFormatterWithLowercaseVendorNameIsCorrectlyBeingUsed() {
		$mockFormatter = $this->getMock('TYPO3\Flow\I18n\Formatter\FormatterInterface');
		$mockFormatter->expects($this->once())
			->method('format')
			->with(123, $this->sampleLocale, array())
			->will($this->returnValue('FormatterOutput42'));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager
			->expects($this->once())
			->method('isRegistered')
			->with('acme\Foo\SampleFormatter')
			->will($this->returnValue(TRUE));
		$mockObjectManager
			->expects($this->once())
			->method('get')
			->with('acme\Foo\SampleFormatter')
			->will($this->returnValue($mockFormatter));

		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$mockReflectionService
			->expects($this->once())
			->method('isClassImplementationOf')
			->with('acme\Foo\SampleFormatter', 'TYPO3\Flow\I18n\Formatter\FormatterInterface')
			->will($this->returnValue(TRUE));

		$formatResolver = new \TYPO3\Flow\I18n\FormatResolver();
		$formatResolver->injectObjectManager($mockObjectManager);
		$this->inject($formatResolver, 'reflectionService', $mockReflectionService);
		$actual = $formatResolver->resolvePlaceholders('{0,acme\Foo\SampleFormatter}', array(123), $this->sampleLocale);
		$this->assertEquals('FormatterOutput42', $actual);
	}

	/**
	 * @test
	 */
	public function namedPlaceholdersAreResolvedCorrectly() {
		$formatResolver = $this->getMock('TYPO3\Flow\I18n\FormatResolver', array('dummy'));

		$result = $formatResolver->resolvePlaceholders('Key {keyName} is {valueName}', array('keyName' => 'foo', 'valueName' => 'bar'), $this->sampleLocale);
		$this->assertEquals('Key foo is bar', $result);
	}
}

?>