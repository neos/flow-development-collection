<?php

declare(strict_types=1);

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
use Neos\Flow\Property\Exception\InvalidDataTypeException;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\CollectionConverter;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Collection converter
 */
final class CollectionConverterTest extends UnitTestCase
{
    /**
     * @var CollectionConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new CollectionConverter();
    }

    #[Test]
    public function checkMetadata()
    {
        self::assertEquals(['string', 'array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('Doctrine\Common\Collections\Collection', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    #[Test]
    public function getTypeOfChildPropertyReturnsElementTypeFromTargetTypeIfGiven()
    {
        self::assertEquals('FooBar', $this->converter->getTypeOfChildProperty('array<FooBar>', '', $this->createStub(PropertyMappingConfigurationInterface::class)));
    }

    #[Test]
    public function getTypeOfChildPropertyThrowsExceptionForMissingElementType()
    {
        $this->expectException(InvalidDataTypeException::class);
        $this->converter->getTypeOfChildProperty('array', 'collection', $this->createStub(PropertyMappingConfigurationInterface::class));
    }
}
