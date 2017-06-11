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

use TYPO3\Flow\Property\TypeConverter\UriTypeConverter;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Http;
use TYPO3\Flow\Error as FlowError;

/**
 * Testcase for the URI type converter
 */
class UriTypeConverterTest extends UnitTestCase
{
    /**
     * @var UriTypeConverter
     */
    protected $typeConverter;

    /**
     */
    protected function setUp()
    {
        parent::setUp();
        $this->typeConverter = new UriTypeConverter();
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
        $this->assertSame(Http\Uri::class, $this->typeConverter->getSupportedTargetType());
    }

    /**
     * @test
     */
    public function typeConverterReturnsUriOnValidUri()
    {
        $this->assertInstanceOf(Http\Uri::class, $this->typeConverter->convertFrom('http://localhost/foo', Http\Uri::class));
    }

    /**
     * @test
     */
    public function typeConverterReturnsErrorOnMalformedUri()
    {
        $actual = $this->typeConverter->convertFrom('http:////localhost', Http\Uri::class);
        $this->assertInstanceOf(FlowError\Error::class, $actual);
    }
}
