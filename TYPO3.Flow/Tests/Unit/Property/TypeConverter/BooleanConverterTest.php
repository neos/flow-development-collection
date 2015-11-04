<?php
namespace TYPO3\Flow\Tests\Unit\Property\TypeConverter;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the Boolean converter
 *
 */
class BooleanConverterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Property\TypeConverter\BooleanConverter
     */
    protected $converter;

    public function setUp()
    {
        $this->converter = new \TYPO3\Flow\Property\TypeConverter\BooleanConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(array('boolean', 'string', 'integer', 'float'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('boolean', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromDoesNotModifyTheBooleanSource()
    {
        $source = true;
        $this->assertSame($source, $this->converter->convertFrom($source, 'boolean'));
    }

    /**
     * @test
     */
    public function convertFromCastsSourceStringToBoolean()
    {
        $source = 'true';
        $this->assertTrue($this->converter->convertFrom($source, 'boolean'));
    }

    /**
     * @test
     */
    public function convertFromCastsNumericSourceStringToBoolean()
    {
        $source = '1';
        $this->assertTrue($this->converter->convertFrom($source, 'boolean'));
    }

    public function convertFromDataProvider()
    {
        return array(
            array('', false),
            array('0', false),
            array('1', true),
            array('false', false),
            array('true', true),
            array('some string', true),
            array('FaLsE', false),
            array('tRuE', true),
            array('tRuE', true),
            array('off', false),
            array('N', false),
            array('no', false),
            array('not no', true),
            array(true, true),
            array(false, false),
            array(1, true),
            array(0, false),
            array(1.0, true),
        );
    }

    /**
     * @test
     * @param mixed $source
     * @param boolean $expected
     * @dataProvider convertFromDataProvider
     */
    public function convertFromTests($source, $expected)
    {
        $this->assertSame($expected, $this->converter->convertFrom($source, 'boolean'));
    }
}
