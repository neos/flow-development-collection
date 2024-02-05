<?php
namespace Neos\Flow\Tests\Unit\Persistence\Doctrine\DataTypes;

/*
* This file is part of the Neos.Flow package.
*
* (c) Contributors of the Neos Project - www.neos.io
*
* This package is Open Source Software. For the full copyright and license
* information, please view the LICENSE file which was distributed with this
* source code.
*/

use Neos\Flow\Persistence\Doctrine\DataTypes\JsonArrayType;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\ArrayBasedValueObject;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\BooleanBasedValueObject;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\FloatBasedValueObject;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\IntegerBasedValueObject;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\StringBasedValueObject;
use Neos\Flow\Tests\UnitTestCase;

class JsonArrayTypeTest extends UnitTestCase
{
    /**
     * @var JsonArrayType|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $jsonArrayTypeMock;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $abstractPlatformMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->jsonArrayTypeMock = $this->getMockBuilder(JsonArrayType::class)
            ->onlyMethods(['initializeDependencies'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractPlatformMock = $this->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')->getMock();
    }

    /**
     * @test
     */
    public function jsonConversionReturnsNullIfArrayIsNull()
    {
        $json = $this->jsonArrayTypeMock->convertToDatabaseValue(null, $this->abstractPlatformMock);
        self::assertEquals(null, $json);
    }

    /**
     * @test
     */
    public function passSimpleArrayAndConvertToJson(): void
    {
        $this->inject($this->jsonArrayTypeMock, 'persistenceManager', $this->createMock(PersistenceManagerInterface::class));
        $json = $this->jsonArrayTypeMock->convertToDatabaseValue(['simplestring',1,['nestedArray']], $this->abstractPlatformMock);
        self::assertEquals("{\n    \"0\": \"simplestring\",\n    \"1\": 1,\n    \"2\": {\n        \"0\": \"nestedArray\"\n    }\n}", $json);
    }

    /**
     * @test
     * @return void
     */
    public function convertsValueObjectsToSerializableArrayStructures(): void
    {
        $this->assertEquals(
            [
                '__value_object_type' => ArrayBasedValueObject::class,
                '__value_object_value' => [
                    'key' => 'value'
                ]
            ],
            JsonArrayType::serializeValueObject(
                ArrayBasedValueObject::fromArray([
                    'key' => 'value'
                ])
            )
        );

        $this->assertEquals(
            [
                '__value_object_type' => StringBasedValueObject::class,
                '__value_object_value' => 'Hello World'
            ],
            JsonArrayType::serializeValueObject(
                StringBasedValueObject::fromString('Hello World')
            )
        );

        $this->assertEquals(
            [
                '__value_object_type' => BooleanBasedValueObject::class,
                '__value_object_value' => true
            ],
            JsonArrayType::serializeValueObject(
                BooleanBasedValueObject::fromBool(true)
            )
        );

        $this->assertEquals(
            [
                '__value_object_type' => IntegerBasedValueObject::class,
                '__value_object_value' => 12
            ],
            JsonArrayType::serializeValueObject(
                IntegerBasedValueObject::fromInt(12)
            )
        );

        $this->assertEquals(
            [
                '__value_object_type' => FloatBasedValueObject::class,
                '__value_object_value' => 55.55
            ],
            JsonArrayType::serializeValueObject(
                FloatBasedValueObject::fromFloat(55.55)
            )
        );

        $this->assertEquals(
            [
                '__value_object_type' => ArrayBasedValueObject::class,
                '__value_object_value' => [
                    'array' => [
                        'key' => 'value'
                    ],
                    'string' => 'string value',
                    'boolean' => false,
                    'integer' => 23,
                    'float' => 22.22
                ]
            ],
            JsonArrayType::serializeValueObject(
                ArrayBasedValueObject::fromArray([
                    'array' => ArrayBasedValueObject::fromArray([
                        'key' => 'value'
                    ]),
                    'boolean' => BooleanBasedValueObject::fromBool(false),
                    'string' => StringBasedValueObject::fromString('string value'),
                    'integer' => IntegerBasedValueObject::fromInt(23),
                    'float' => FloatBasedValueObject::fromFloat(22.22)
                ])
            )
        );
    }

    /**
     * @test
     * @return void
     */
    public function deserializesValueObjectsFromSerializableArrayStructures(): void
    {
        //
        // Array
        //
        $valueObject = JsonArrayType::deserializeValueObject([
            '__value_object_type' => ArrayBasedValueObject::class,
            '__value_object_value' => [
                'key' => 'value'
            ]
        ]);

        $this->assertInstanceOf(ArrayBasedValueObject::class, $valueObject);
        /** @var ArrayBasedValueObject $valueObject */
        $this->assertEquals(['key' => 'value'], $valueObject->getValue());

        //
        // String
        //
        $valueObject = JsonArrayType::deserializeValueObject([
            '__value_object_type' => StringBasedValueObject::class,
            '__value_object_value' => 'Hello World!'
        ]);

        $this->assertInstanceOf(StringBasedValueObject::class, $valueObject);
        /** @var StringBasedValueObject $valueObject */
        $this->assertEquals('Hello World!', $valueObject->getValue());

        //
        // Boolean
        //
        $valueObject = JsonArrayType::deserializeValueObject([
            '__value_object_type' => BooleanBasedValueObject::class,
            '__value_object_value' => false
        ]);

        $this->assertInstanceOf(BooleanBasedValueObject::class, $valueObject);
        /** @var BooleanBasedValueObject $valueObject */
        $this->assertEquals(false, $valueObject->getValue());

        //
        // Integer
        //
        $valueObject = JsonArrayType::deserializeValueObject([
            '__value_object_type' => IntegerBasedValueObject::class,
            '__value_object_value' => 87
        ]);

        $this->assertInstanceOf(IntegerBasedValueObject::class, $valueObject);
        /** @var IntegerBasedValueObject $valueObject */
        $this->assertEquals(87, $valueObject->getValue());

        //
        // Float
        //
        $valueObject = JsonArrayType::deserializeValueObject([
            '__value_object_type' => FloatBasedValueObject::class,
            '__value_object_value' => 17.777
        ]);

        $this->assertInstanceOf(FloatBasedValueObject::class, $valueObject);
        /** @var FloatBasedValueObject $valueObject */
        $this->assertEquals(17.777, $valueObject->getValue());
    }
}
