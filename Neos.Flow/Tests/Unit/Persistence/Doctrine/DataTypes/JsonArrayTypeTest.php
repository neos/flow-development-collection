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
use Neos\Flow\Tests\UnitTestCase;

class JsonArrayTypeTest extends UnitTestCase
{
    /**
     * @var JsonArrayType|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jsonArrayTypeMock;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $abstractPlatformMock;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->jsonArrayTypeMock = $this->getMockBuilder(JsonArrayType::class)
            ->setMethods(['initializeDependencies'])
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
        $this->assertEquals(null, $json);
    }

    /**
     * @test
     */
    public function passSimpleArrayAndConvertToJson()
    {
        $json = $this->jsonArrayTypeMock->convertToDatabaseValue(['simplestring',1,['nestedArray']], $this->abstractPlatformMock);
        $this->assertEquals("{\n    \"0\": \"simplestring\",\n    \"1\": 1,\n    \"2\": {\n        \"0\": \"nestedArray\"\n    }\n}", $json);
    }
}
