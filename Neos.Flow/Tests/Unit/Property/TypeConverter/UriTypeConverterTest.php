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

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Property\TypeConverter\UriTypeConverter;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Error\Messages as FlowError;
use Psr\Http\Message\UriInterface;

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
    protected function setUp(): void
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
        self::assertCount(1, $sourceTypes);
        self::assertSame('string', $sourceTypes[0]);
    }

    /**
     * @test
     */
    public function targetTypeIsUri()
    {
        self::assertSame(UriInterface::class, $this->typeConverter->getSupportedTargetType());
    }

    /**
     * @test
     */
    public function typeConverterReturnsUriOnValidUri()
    {
        self::assertInstanceOf(Uri::class, $this->typeConverter->convertFrom('http://localhost/foo', Uri::class));
    }

    /**
     * @test
     */
    public function typeConverterReturnsErrorOnMalformedUri()
    {
        $actual = $this->typeConverter->convertFrom('http:////localhost', Uri::class);
        self::assertInstanceOf(FlowError\Error::class, $actual);
    }
}
