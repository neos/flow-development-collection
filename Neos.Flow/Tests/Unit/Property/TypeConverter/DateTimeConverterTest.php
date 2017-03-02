<?php
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

use Neos\Flow\Property\TypeConverter\DateTimeConverter;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Error\Messages\Error as FlowError;

/**
 * Testcase for the DateTime converter
 *
 * @covers \Neos\Flow\Property\TypeConverter\DateTimeConverter<extended>
 */
class DateTimeConverterTest extends UnitTestCase
{
    /**
     * @var DateTimeConverter
     */
    protected $converter;

    public function setUp()
    {
        $this->converter = new DateTimeConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['string', 'integer', 'array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals(\DateTimeInterface::class, $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }


    /** String to DateTime testcases  **/

    /**
     * @test
     */
    public function canConvertFromReturnsFalseIfTargetTypeIsNotDateTime()
    {
        $this->assertFalse($this->converter->canConvertFrom('Foo', 'SomeOtherType'));
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAString()
    {
        $this->assertTrue($this->converter->canConvertFrom('Foo', 'DateTime'));
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAnEmptyString()
    {
        $this->assertTrue($this->converter->canConvertFrom('', 'DateTime'));
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueITargetTypeIsADateTimeImmutable()
    {
        $this->assertTrue($this->converter->canConvertFrom('', \DateTimeImmutable::class));
    }

    /**
     * @test
     */
    public function convertFromReturnsErrorIfGivenStringCantBeConverted()
    {
        $error = $this->converter->convertFrom('1980-12-13', 'DateTime');
        $this->assertInstanceOf(FlowError::class, $error);
    }

    /**
     * @test
     */
    public function convertFromProperlyConvertsStringWithDefaultDateFormat()
    {
        $expectedResult = '1980-12-13T20:15:07+01:23';
        $date = $this->converter->convertFrom($expectedResult, 'DateTime');
        $actualResult = $date->format('Y-m-d\TH:i:sP');
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function convertFromProperlyConvertsToDateTimeImmutable()
    {
        $expectedResult = '1980-12-13T20:15:07+01:23';
        $date = $this->converter->convertFrom($expectedResult, \DateTimeImmutable::class);
        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
    }

    /**
     * @test
     */
    public function convertFromUsesDefaultDateFormatIfItIsNotConfigured()
    {
        $expectedResult = '1980-12-13T20:15:07+01:23';
        $mockMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
        $mockMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('getConfigurationValue')
            ->with(DateTimeConverter::class, DateTimeConverter::CONFIGURATION_DATE_FORMAT)
            ->will($this->returnValue(null));

        $date = $this->converter->convertFrom($expectedResult, 'DateTime', [], $mockMappingConfiguration);
        $actualResult = $date->format(DateTimeConverter::DEFAULT_DATE_FORMAT);
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function convertFromEmptyStringReturnsNull()
    {
        $date = $this->converter->convertFrom('', 'DateTime', [], null);
        $this->assertNull($date);
    }

    /**
     * @return array
     * @see convertFromStringTests()
     */
    public function convertFromStringDataProvider()
    {
        return [
            ['1308174051', '', false],
            ['13-12-1980', 'd.m.Y', false],
            ['1308174051', 'Y-m-d', false],
            ['12:13', 'H:i', true],
            ['13.12.1980', 'd.m.Y', true],
            ['2005-08-15T15:52:01+00:00', null, true],
            ['2005-08-15T15:52:01+0000', \DateTime::ISO8601, true],
            ['1308174051', 'U', true],
        ];
    }

    /**
     * @param string $source the string to be converted
     * @param string $dateFormat the expected date format
     * @param boolean $isValid TRUE if the conversion is expected to be successful, otherwise FALSE
     * @test
     * @dataProvider convertFromStringDataProvider
     */
    public function convertFromStringTests($source, $dateFormat, $isValid)
    {
        if ($dateFormat !== null) {
            $mockMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
            $mockMappingConfiguration
                ->expects($this->atLeastOnce())
                ->method('getConfigurationValue')
                ->with(DateTimeConverter::class, DateTimeConverter::CONFIGURATION_DATE_FORMAT)
                ->will($this->returnValue($dateFormat));
        } else {
            $mockMappingConfiguration = null;
        }
        $date = $this->converter->convertFrom($source, 'DateTime', [], $mockMappingConfiguration);
        if ($isValid !== true) {
            $this->assertInstanceOf(FlowError::class, $date);
            return;
        }
        $this->assertInstanceOf(\DateTime::class, $date);

        if ($dateFormat === null) {
            $dateFormat = DateTimeConverter::DEFAULT_DATE_FORMAT;
        }
        $this->assertSame($source, $date->format($dateFormat));
    }

    /**
     * @return array
     * @see convertFromIntegerOrDigitStringWithoutConfigurationTests()
     * @see convertFromIntegerOrDigitStringInArrayWithoutConfigurationTests()
     */
    public function convertFromIntegerOrDigitStringsWithoutConfigurationDataProvider()
    {
        return [
            ['1308174051'],
            [1308174051],
        ];
    }

    /**
     * @test
     * @param $source
     * @dataProvider convertFromIntegerOrDigitStringsWithoutConfigurationDataProvider
     */
    public function convertFromIntegerOrDigitStringWithoutConfigurationTests($source)
    {
        $date = $this->converter->convertFrom($source, 'DateTime', [], null);
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertSame(strval($source), $date->format('U'));
    }

    /**
     * @return array
     * @see convertFromIntegerOrDigitStringWithoutConfigurationTests()
     * @see convertFromIntegerOrDigitStringInArrayWithoutConfigurationTests()
     */
    public function convertFromIntegerOrDigitStringsWithConfigurationWithoutFormatDataProvider()
    {
        return array(
            array('1308174051'),
            array(1308174051),
        );
    }

    /**
     * @test
     * @param $source
     * @dataProvider convertFromIntegerOrDigitStringsWithConfigurationWithoutFormatDataProvider
     */
    public function convertFromIntegerOrDigitStringWithConfigurationWithoutFormatTests($source)
    {
        $mockMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
        $mockMappingConfiguration
            ->expects($this->atLeastOnce())
            ->method('getConfigurationValue')
            ->with(DateTimeConverter::class, DateTimeConverter::CONFIGURATION_DATE_FORMAT)
            ->will($this->returnValue(null));

        $date = $this->converter->convertFrom($source, 'DateTime', array(), $mockMappingConfiguration);
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertSame(strval($source), $date->format('U'));
    }

    /** Array to DateTime testcases  **/

    /**
     * @test
     * @param $source
     * @dataProvider convertFromIntegerOrDigitStringsWithoutConfigurationDataProvider
     */
    public function convertFromIntegerOrDigitStringInArrayWithoutConfigurationTests($source)
    {
        $date = $this->converter->convertFrom(['date' => $source], 'DateTime', [], null);
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertSame(strval($source), $date->format('U'));
    }

    /**
     * @test
     */
    public function canConvertFromReturnsTrueIfSourceTypeIsAnArray()
    {
        $this->assertTrue($this->converter->canConvertFrom([], 'DateTime'));
    }

    /**
     * @test
     */
    public function convertFromReturnsErrorIfGivenArrayCantBeConverted()
    {
        $error = $this->converter->convertFrom(['date' => '1980-12-13'], 'DateTime');
        $this->assertInstanceOf(FlowError::class, $error);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Property\Exception\TypeConverterException
     */
    public function convertFromThrowsExceptionIfGivenArrayDoesNotSpecifyTheDate()
    {
        $this->converter->convertFrom(['hour' => '12', 'minute' => '30'], 'DateTime');
    }

    /**
     * @test
     */
    public function convertFromProperlyConvertsArrayWithDefaultDateFormat()
    {
        $expectedResult = '1980-12-13T20:15:07+01:23';
        $date = $this->converter->convertFrom(['date' => $expectedResult], 'DateTime');
        $actualResult = $date->format('Y-m-d\TH:i:sP');
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @return array
     * @see convertFromThrowsExceptionIfDatePartKeysHaveInvalidValuesSpecified
     */
    public function invalidDatePartKeyValuesDataProvider()
    {
        return [
            [['day' => '13.0', 'month' => '10', 'year' => '2010']],
            [['day' => '13', 'month' => '10.0', 'year' => '2010']],
            [['day' => '13', 'month' => '10', 'year' => '2010.0']],
            [['day' => '-13', 'month' => '10', 'year' => '2010']],
            [['day' => '13', 'month' => '-10', 'year' => '2010']],
            [['day' => '13', 'month' => '10', 'year' => '-2010']],
        ];
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Property\Exception\TypeConverterException
     * @dataProvider invalidDatePartKeyValuesDataProvider
     */
    public function convertFromThrowsExceptionIfDatePartKeysHaveInvalidValuesSpecified($source)
    {
        $this->converter->convertFrom($source, 'DateTime');
    }

    /**
     * @test
     */
    public function convertFromProperlyConvertsArrayWithDateAsArray()
    {
        $source = ['day' => '13', 'month' => '10', 'year' => '2010'];
        $mappingConfiguration = new PropertyMappingConfiguration();
        $mappingConfiguration->setTypeConverterOption(
            DateTimeConverter::class,
            DateTimeConverter::CONFIGURATION_DATE_FORMAT,
            'Y-m-d'
        );

        $date = $this->converter->convertFrom($source, 'DateTime', [], $mappingConfiguration);
        $actualResult = $date->format('Y-m-d');
        $this->assertSame('2010-10-13', $actualResult);
    }

    /**
     * @test
     */
    public function convertFromAllowsToOverrideTheTime()
    {
        $source = [
            'date' => '2011-06-16',
            'dateFormat' => 'Y-m-d',
            'hour' => '12',
            'minute' => '30',
            'second' => '59',
        ];
        $date = $this->converter->convertFrom($source, 'DateTime');
        $this->assertSame('2011-06-16', $date->format('Y-m-d'));
        $this->assertSame('12', $date->format('H'));
        $this->assertSame('30', $date->format('i'));
        $this->assertSame('59', $date->format('s'));
    }

    /**
     * @test
     */
    public function convertFromAllowsToOverrideTheTimezone()
    {
        $source = [
            'date' => '2011-06-16 12:30:59',
            'dateFormat' => 'Y-m-d H:i:s',
            'timezone' => 'Atlantic/Reykjavik',
        ];
        $date = $this->converter->convertFrom($source, 'DateTime');
        $this->assertSame('2011-06-16', $date->format('Y-m-d'));
        $this->assertSame('12', $date->format('H'));
        $this->assertSame('30', $date->format('i'));
        $this->assertSame('59', $date->format('s'));
        $this->assertSame('Atlantic/Reykjavik', $date->getTimezone()->getName());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Property\Exception\TypeConverterException
     */
    public function convertFromThrowsExceptionIfSpecifiedTimezoneIsInvalid()
    {
        $source = [
            'date' => '2011-06-16',
            'dateFormat' => 'Y-m-d',
            'timezone' => 'Invalid/Timezone',
        ];
        $this->converter->convertFrom($source, 'DateTime');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Property\Exception\TypeConverterException
     */
    public function convertFromArrayThrowsExceptionForEmptyArray()
    {
        $this->converter->convertFrom([], 'DateTime', [], null);
    }

    /**
     * @test
     */
    public function convertFromArrayReturnsNullForEmptyDate()
    {
        $this->assertNull($this->converter->convertFrom(['date' => ''], 'DateTime', [], null));
    }

    /**
     * @return array
     * @see convertFromArrayTests()
     */
    public function convertFromArrayDataProvider()
    {
        return [
            [['date' => '2005-08-15T15:52:01+01:00'], true],
            [['date' => '1308174051', 'dateFormat' => ''], true],
            [['date' => '13-12-1980', 'dateFormat' => 'd.m.Y'], false],
            [['date' => '1308174051', 'dateFormat' => 'Y-m-d'], false],
            [['date' => '12:13', 'dateFormat' => 'H:i'], true],
            [['date' => '13.12.1980', 'dateFormat' => 'd.m.Y'], true],
            [['date' => '2005-08-15T15:52:01+00:00', 'dateFormat' => ''], true],
            [['date' => '2005-08-15T15:52:01+0000', 'dateFormat' => \DateTime::ISO8601], true],
            [['date' => '1308174051', 'dateFormat' => 'U'], true],
            [['date' => 1308174051, 'dateFormat' => 'U'], true],
        ];
    }

    /**
     * @param array $source the array to be converted
     * @param boolean $isValid TRUE if the conversion is expected to be successful, otherwise FALSE
     * @test
     * @dataProvider convertFromArrayDataProvider
     */
    public function convertFromArrayTests(array $source, $isValid)
    {
        $dateFormat = isset($source['dateFormat']) && strlen($source['dateFormat']) > 0 ? $source['dateFormat'] : null;
        if ($dateFormat !== null) {
            $mockMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
            $mockMappingConfiguration
                ->expects($this->atLeastOnce())
                ->method('getConfigurationValue')
                ->with(DateTimeConverter::class, DateTimeConverter::CONFIGURATION_DATE_FORMAT)
                ->will($this->returnValue($dateFormat));
        } else {
            $mockMappingConfiguration = null;
        }
        $date = $this->converter->convertFrom($source, 'DateTime', [], $mockMappingConfiguration);

        if ($isValid !== true) {
            $this->assertInstanceOf(FlowError::class, $date);
            return;
        }

        $this->assertInstanceOf(\DateTime::class, $date);
        $dateAsString = isset($source['date']) ? strval($source['date']) : '';
        if ($dateFormat === null) {
            if (ctype_digit($dateAsString)) {
                $dateFormat = 'U';
            } else {
                $dateFormat = DateTimeConverter::DEFAULT_DATE_FORMAT;
            }
        }
        $this->assertSame($dateAsString, $date->format($dateFormat));
    }

    /**
     * @test
     */
    public function convertFromSupportsDateTimeSubClasses()
    {
        $className = 'DateTimeSubClass' . md5(uniqid(mt_rand(), true));
        if (version_compare(PHP_VERSION, '7.0.0-dev')) {
            eval('
			class ' . $className . ' extends \\DateTime {
				public static function createFromFormat($format, $time, $timezone = NULL) {
					return new ' . $className . '();
				}
				public function foo() { return "Bar"; }
			}
		');
        } else {
            eval('
				class ' . $className . ' extends \\DateTime {
					public static function createFromFormat($format, $time, \\DateTimeZone $timezone = NULL) {
						return new ' . $className . '();
					}
					public function foo() { return "Bar"; }
				}
			');
        }
        $date = $this->converter->convertFrom('2005-08-15T15:52:01+00:00', $className);

        $this->assertInstanceOf($className, $date);
        $this->assertSame('Bar', $date->foo());
    }
}
