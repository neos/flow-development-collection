<?php
namespace TYPO3\Flow\Tests\Unit\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\MediaTypeConverter;
use TYPO3\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MediaTypeConverter
 */
class MediaTypeConverterTest extends UnitTestCase {

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
	public function setUp() {
		$this->mediaTypeConverter = new MediaTypeConverter();

		$this->mockPropertyMappingConfiguration = $this->getMockBuilder('TYPO3\Flow\Property\PropertyMappingConfigurationInterface')->getMock();
	}

	/**
	 * @test
	 */
	public function convertExpectsJsonAsDefault() {
		$actualResult = $this->mediaTypeConverter->convertFrom('{"jsonArgument":"jsonValue"}', 'array');
		$expectedResult = array('jsonArgument' => 'jsonValue');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function convertReturnsEmptyArrayIfBodyCantBeParsed() {
		$actualResult = $this->mediaTypeConverter->convertFrom('<root><xmlArgument>xmlValue</xmlArgument></root>', 'array');
		$expectedResult = array();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function convertReturnsEmptyArrayIfGivenMediaTypeIsInvalid() {
		$this->mockPropertyMappingConfiguration->expects($this->atLeastOnce())->method('getConfigurationValue')->with('TYPO3\Flow\Property\TypeConverter\MediaTypeConverterInterface', MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE)->will($this->returnValue('someInvalidMediaType'));

		$actualResult = $this->mediaTypeConverter->convertFrom('{"jsonArgument":"jsonValue"}', 'array', array(), $this->mockPropertyMappingConfiguration);
		$expectedResult = array();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * Data provider
	 */
	public function contentTypesBodiesAndExpectedUnifiedArguments() {
		return array(
			array('application/json', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
			array('application/json', 'invalid json source code', array()),
			array('application/json; charset=UTF-8', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
			array('application/xml', '<root><xmlArgument>xmlValue</xmlArgument></root>', array('xmlArgument' => 'xmlValue')),
			array('text/xml', '<root><xmlArgument>xmlValue</xmlArgument><![CDATA[<!-- text/xml is, by the way, meant to be readable by "the casual user" -->]]></root>', array('xmlArgument' => 'xmlValue')),
			array('text/xml', '<invalid xml source code>', array()),
			array('application/xml;charset=UTF8', '<root><xmlArgument>xmlValue</xmlArgument></root>', array('xmlArgument' => 'xmlValue')),

			// the following media types are wrong (not registered at IANA), but still used by some out there:

			array('application/x-javascript', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
			array('text/javascript', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
			array('text/x-javascript', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
			array('text/x-json', '{"jsonArgument":"jsonValue"}', array('jsonArgument' => 'jsonValue')),
		);
	}

	/**
	 * @test
	 * @dataProvider contentTypesBodiesAndExpectedUnifiedArguments
	 */
	public function convertTests($mediaType, $requestBody, array $expectedResult) {
		$this->mockPropertyMappingConfiguration->expects($this->atLeastOnce())->method('getConfigurationValue')->with('TYPO3\Flow\Property\TypeConverter\MediaTypeConverterInterface', MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE)->will($this->returnValue($mediaType));

		$actualResult = $this->mediaTypeConverter->convertFrom($requestBody, 'array', array(), $this->mockPropertyMappingConfiguration);
		$this->assertSame($expectedResult, $actualResult);
	}
}