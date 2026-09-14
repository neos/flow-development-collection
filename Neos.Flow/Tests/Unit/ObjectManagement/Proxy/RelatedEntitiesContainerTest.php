<?php

namespace Neos\Flow\Tests\Unit\ObjectManagement\Proxy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ObjectManagement\Proxy\RelatedEntitiesContainer;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\SomeEntity;
use Neos\Flow\Tests\Unit\ObjectManagement\Fixture\SomeEntityDoctrineProxy;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

require_once(__DIR__ . '/../Fixture/SomeEntity.php');
require_once(__DIR__ . '/../Fixture/SomeEntityDoctrineProxy.php');

/**
 * Test cases for the RelatedEntitiesContainer
 */
class RelatedEntitiesContainerTest extends UnitTestCase
{
    protected ObjectManagerInterface|MockObject $mockObjectManager;
    protected PersistenceManagerInterface|MockObject $mockPersistenceManager;

    /**
     * The object manager which was set before this test case replaced it
     */
    protected ?ObjectManagerInterface $originalStaticObjectManager;

    protected function setUp(): void
    {
        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->method('get')->with(PersistenceManagerInterface::class)->willReturn($this->mockPersistenceManager);

        $this->originalStaticObjectManager = Bootstrap::$staticObjectManager;
        Bootstrap::$staticObjectManager = $this->mockObjectManager;
    }

    protected function tearDown(): void
    {
        Bootstrap::$staticObjectManager = $this->originalStaticObjectManager;
    }

    #[Test]
    public function aNewContainerIsEmpty(): void
    {
        self::assertSame([], iterator_to_array((new RelatedEntitiesContainer())->getIterator()));
    }

    #[Test]
    public function appendRelatedEntityStoresPropertyNamePathClassNameAndIdentifier(): void
    {
        $entity = new SomeEntity();
        $this->mockObjectManager->method('getObjectNameByClassName')->with(SomeEntity::class)->willReturn(SomeEntity::class);
        $this->mockPersistenceManager->method('getIdentifierByObject')->with($entity)->willReturn('the-identifier');

        $container = new RelatedEntitiesContainer();
        $container->appendRelatedEntity('someProperty', 'foo.bar', $entity);

        self::assertSame(
            [
                [
                    'n' => 'someProperty',
                    'c' => SomeEntity::class,
                    'i' => 'the-identifier',
                    'p' => 'foo.bar'
                ]
            ],
            iterator_to_array($container->getIterator())
        );
    }

    #[Test]
    public function appendRelatedEntityUsesTheObjectNameOfTheGivenEntity(): void
    {
        $entity = new SomeEntity();
        $this->mockObjectManager->expects(self::once())->method('getObjectNameByClassName')->with(SomeEntity::class)->willReturn('Some.Package:SomeEntity');
        $this->mockPersistenceManager->method('getIdentifierByObject')->willReturn('the-identifier');

        $container = new RelatedEntitiesContainer();
        $container->appendRelatedEntity('someProperty', '', $entity);

        self::assertSame('Some.Package:SomeEntity', iterator_to_array($container->getIterator())[0]['c']);
    }

    #[Test]
    public function appendRelatedEntityUsesTheParentClassNameForDoctrineProxies(): void
    {
        $this->mockObjectManager->expects(self::never())->method('getObjectNameByClassName');
        $this->mockPersistenceManager->method('getIdentifierByObject')->willReturn('the-identifier');

        $container = new RelatedEntitiesContainer();
        $container->appendRelatedEntity('someProperty', '', new SomeEntityDoctrineProxy());

        self::assertSame(SomeEntity::class, iterator_to_array($container->getIterator())[0]['c']);
    }

    #[Test]
    public function appendRelatedEntityFallsBackToTheDoctrineProxyIdentifierIfThePersistenceManagerDoesNotKnowTheObject(): void
    {
        $this->mockPersistenceManager->method('getIdentifierByObject')->willReturn(null);

        $container = new RelatedEntitiesContainer();
        $container->appendRelatedEntity('someProperty', '', new SomeEntityDoctrineProxy());

        self::assertSame('identifier-from-doctrine-proxy', iterator_to_array($container->getIterator())[0]['i']);
    }

    #[Test]
    public function appendRelatedEntityStoresMultipleEntitiesInTheOrderTheyWereAdded(): void
    {
        $this->mockObjectManager->method('getObjectNameByClassName')->willReturn(SomeEntity::class);
        $this->mockPersistenceManager->method('getIdentifierByObject')->willReturnOnConsecutiveCalls('first', 'second');

        $container = new RelatedEntitiesContainer();
        $container->appendRelatedEntity('someProperty', 'foo', new SomeEntity());
        $container->appendRelatedEntity('someOtherProperty', 'bar', new SomeEntity());

        $entityInformations = iterator_to_array($container->getIterator());

        self::assertCount(2, $entityInformations);
        self::assertSame('first', $entityInformations[0]['i']);
        self::assertSame('someProperty', $entityInformations[0]['n']);
        self::assertSame('second', $entityInformations[1]['i']);
        self::assertSame('someOtherProperty', $entityInformations[1]['n']);
    }

    #[Test]
    public function appendRelatedEntityOverwritesAnEntryWithTheSamePropertyNameAndPath(): void
    {
        $this->mockObjectManager->method('getObjectNameByClassName')->willReturn(SomeEntity::class);
        $this->mockPersistenceManager->method('getIdentifierByObject')->willReturnOnConsecutiveCalls('first', 'second');

        $container = new RelatedEntitiesContainer();
        $container->appendRelatedEntity('someProperty', 'foo', new SomeEntity());
        $container->appendRelatedEntity('someProperty', 'foo', new SomeEntity());

        $entityInformations = iterator_to_array($container->getIterator());

        self::assertCount(1, $entityInformations);
        self::assertSame('second', $entityInformations[0]['i']);
    }

    #[Test]
    public function resetRemovesAllPreviouslyAddedEntities(): void
    {
        $this->mockObjectManager->method('getObjectNameByClassName')->willReturn(SomeEntity::class);
        $this->mockPersistenceManager->method('getIdentifierByObject')->willReturn('the-identifier');

        $container = new RelatedEntitiesContainer();
        $container->appendRelatedEntity('someProperty', '', new SomeEntity());
        self::assertCount(1, iterator_to_array($container->getIterator()));

        $container->reset();

        self::assertSame([], iterator_to_array($container->getIterator()));
    }

    #[Test]
    public function theContainerCanBeIteratedDirectly(): void
    {
        $this->mockObjectManager->method('getObjectNameByClassName')->willReturn(SomeEntity::class);
        $this->mockPersistenceManager->method('getIdentifierByObject')->willReturn('the-identifier');

        $container = new RelatedEntitiesContainer();
        $container->appendRelatedEntity('someProperty', 'foo', new SomeEntity());

        $collectedPropertyNames = [];
        foreach ($container as $entityInformation) {
            $collectedPropertyNames[] = $entityInformation['n'];
        }

        self::assertSame(['someProperty'], $collectedPropertyNames);
    }
}
