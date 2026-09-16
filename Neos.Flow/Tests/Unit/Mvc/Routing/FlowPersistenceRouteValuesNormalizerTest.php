<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Routing;

use Neos\Flow\Mvc\Routing\FlowPersistenceRouteValuesNormalizer;
use Neos\Flow\Persistence\Exception\UnknownObjectException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

class FlowPersistenceRouteValuesNormalizerTest extends UnitTestCase
{
    /**
     * @var PersistenceManagerInterface&MockObject
     */
    protected $persistenceManager;

    /**
     * @var FlowPersistenceRouteValuesNormalizer
     */
    protected $flowPersistenceRouteValuesNormalizer;

    protected function setUp(): void
    {
        $this->persistenceManager = $this->getMockBuilder(PersistenceManagerInterface::class)->getMock();

        $this->flowPersistenceRouteValuesNormalizer = new FlowPersistenceRouteValuesNormalizer($this->persistenceManager);
    }

    #[Test]
    public function normalizeObjectsConvertsAnObject()
    {
        $someObject = new \stdClass();
        $this->persistenceManager->expects(self::once())->method('getIdentifierByObject')->with($someObject)->willReturn(123);

        $expectedResult = [['__identity' => 123]];
        $actualResult = $this->flowPersistenceRouteValuesNormalizer->normalizeObjects([$someObject]);
        self::assertEquals($expectedResult, $actualResult);
    }


    #[Test]
    public function convertObjectToIdentityArrayThrowsExceptionIfIdentityForTheGivenObjectCantBeDetermined()
    {
        $this->expectException(UnknownObjectException::class);
        $someObject = new \stdClass();
        $this->persistenceManager->expects(self::once())->method('getIdentifierByObject')->with($someObject)->willReturn(null);

        $this->flowPersistenceRouteValuesNormalizer->normalizeObjects([$someObject]);
    }

    #[Test]
    public function convertObjectsToIdentityArraysRecursivelyConvertsObjects()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $matcher = self::exactly(2);
        $this->persistenceManager->expects($matcher)->method('getIdentifierByObject')
            ->willReturnCallback(function ($object) use ($matcher, $object1, $object2) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($object1, $object);
                    return 'identifier1';
                }
                self::assertSame($object2, $object);
                return 'identifier2';
            });

        $originalArray = ['foo' => 'bar', 'object1' => $object1, 'baz' => ['object2' => $object2]];
        $expectedResult = ['foo' => 'bar', 'object1' => ['__identity' => 'identifier1'], 'baz' => ['object2' => ['__identity' => 'identifier2']]];

        $actualResult = $this->flowPersistenceRouteValuesNormalizer->normalizeObjects($originalArray);
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function convertObjectsToIdentityArraysConvertsObjectsInIterators()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $matcher = self::exactly(2);
        $this->persistenceManager->expects($matcher)->method('getIdentifierByObject')
            ->willReturnCallback(function ($object) use ($matcher, $object1, $object2) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($object1, $object);
                    return 'identifier1';
                }
                self::assertSame($object2, $object);
                return 'identifier2';
            });

        $originalArray = ['foo' => 'bar', 'object1' => $object1, 'baz' => new \ArrayObject(['object2' => $object2])];
        $expectedResult = ['foo' => 'bar', 'object1' => ['__identity' => 'identifier1'], 'baz' => ['object2' => ['__identity' => 'identifier2']]];

        $actualResult = $this->flowPersistenceRouteValuesNormalizer->normalizeObjects($originalArray);
        self::assertEquals($expectedResult, $actualResult);
    }
}
