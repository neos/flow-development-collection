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
 * Testcase for the URI type converter
 *
 */
class UriTypeConverterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Property\TypeConverter\UriTypeConverter
     */
    protected $typeConverter;

    /**
     */
    protected function setUp()
    {
        parent::setUp();
        $this->typeConverter = new \TYPO3\Flow\Property\TypeConverter\UriTypeConverter();
    }

    /**
     * @test
     */
    public function sourceTypeIsStringOnly()
    {
        $sourceTypes = $this->typeConverter->getSupportedSourceTypes();
        $this->assertCount(1, $sourceTypes);
        $this->assertSame('string', $sourceTypes[0]);
    }

    /**
     * @test
     */
    public function targetTypeIsUri()
    {
        $this->assertSame('TYPO3\Flow\Http\Uri', $this->typeConverter->getSupportedTargetType());
    }

    /**
     * @test
     */
    public function typeConverterReturnsUriOnValidUri()
    {
        $this->assertInstanceOf('TYPO3\Flow\Http\Uri', $this->typeConverter->convertFrom('http://localhost/foo', 'TYPO3\Flow\Http\Uri'));
    }

    /**
     * @test
     */
    public function typeConverterReturnsErrorOnMalformedUri()
    {
        $actual = $this->typeConverter->convertFrom('http:////localhost', 'TYPO3\Flow\Http\Uri');
        $this->assertInstanceOf('TYPO3\Flow\Error\Error', $actual);
    }
}
