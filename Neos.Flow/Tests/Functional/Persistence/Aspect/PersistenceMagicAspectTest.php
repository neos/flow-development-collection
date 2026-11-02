<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Persistence\Aspect;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdentitiesEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdEntity;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestValueObject;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestValueObjectWithConstructorLogic;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestValueObjectWithConstructorLogicAndInversedPropertyOrder;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestValueObjectWithDateTimeProperty;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestValueObjectWithSubValueObjectProperties;
use Neos\Flow\Tests\Functional\Persistence\Fixtures\TestValueObjectWithTransientProperties;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for PersistenceMagicAspect
 */
final class PersistenceMagicAspectTest extends FunctionalTestCase
{
    /**
     * @var bool
     */
    protected static $testablePersistenceEnabled = true;

    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            static::markTestSkipped('Doctrine persistence is not enabled');
        }
    }

    #[Test]
    public function aspectIntroducesUuidIdentifierToEntities(): void
    {
        $entity = new AnnotatedIdentitiesEntity();
        static::assertStringMatchesFormat('%x%x%x%x%x%x%x%x-%x%x%x%x-%x%x%x%x-%x%x%x%x-%x%x%x%x%x%x%x%x', $this->persistenceManager->getIdentifierByObject($entity));
    }

    #[Test]
    public function aspectDoesNotIntroduceUuidIdentifierToEntitiesWithCustomIdProperties(): void
    {
        $entity = new AnnotatedIdEntity();
        self::assertNull($this->persistenceManager->getIdentifierByObject($entity));
    }

    #[Test]
    public function valueHashIsGeneratedForValueObjects(): void
    {
        $valueObject = new TestValueObject('value');

        static::assertObjectHasProperty('Persistence_Object_Identifier', $valueObject);
        self::assertNotEmpty($this->persistenceManager->getIdentifierByObject($valueObject));
    }

    #[DataProvider('sameValueObjectDataProvider')]
    #[Test]
    public function valueObjectsWithTheSamePropertyValuesAreEqual(\Closure $closure): void
    {
        [$valueObject1, $valueObject2] = $closure();
        self::assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
    }

    public static function sameValueObjectDataProvider(): \Iterator
    {
        // These need to be provided as closures so that the construction happens inside the test and not outside of the test environment.
        yield [static fn () => [new TestValueObject('value'), new TestValueObject('value')]];
        yield [static fn () => [new TestValueObjectWithConstructorLogic('val', 'val'), new TestValueObjectWithConstructorLogic(' val', 'val ')]];
        yield [static fn () => [new TestValueObjectWithConstructorLogic('moreThan5Chars', 'alsoMoreButDoesntMatter'), new TestValueObjectWithConstructorLogic('  moreThan5Chars  ', '        alsoMoreButDoesntMatter ')]];
    }

    #[DataProvider('differentValueObjectDataProvider')]
    #[Test]
    public function valueObjectWithDifferentPropertyValuesAreNotEqual(\Closure $closure): void
    {
        [$valueObject1, $valueObject2] = $closure();
        self::assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
    }

    public static function differentValueObjectDataProvider(): \Iterator
    {
        // These need to be provided as closures so that the construction happens inside the test and not outside of the test environment.
        yield [static fn () => [new TestValueObject('value1'), new TestValueObject('value2')]];
        yield [static fn () => [new TestValueObject(''), new TestValueObject(null)]];
        yield [static fn () => [new TestValueObjectWithConstructorLogic('chars', ' value2IsJustTrimmed        '), new TestValueObjectWithConstructorLogic('chars ', '        value2IsJustTrimmed ')]];
    }

    #[Test]
    public function valueHashMustBeUniqueForEachClassIndependentOfPropertiesOrValues(): void
    {
        $valueObject1 = new TestValueObjectWithConstructorLogic('value1', 'value2');
        $valueObject2 = new TestValueObjectWithConstructorLogicAndInversedPropertyOrder('value2', 'value1');

        self::assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
    }

    #[Test]
    public function transientPropertiesAreDisregardedForValueHashGeneration(): void
    {
        $valueObject1 = new TestValueObjectWithTransientProperties('value1', 'thisDoesntRegardPersistenceWhatSoEver');
        $valueObject2 = new TestValueObjectWithTransientProperties('value1', 'reallyThisPropertyIsTransient');

        self::assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
    }

    #[Test]
    public function dateTimeIsDifferentDependingOnTheTimeZone(): void
    {
        $valueObject1 = new TestValueObjectWithDateTimeProperty(new \DateTime('01.01.2013 00:00', new \DateTimeZone('GMT')));
        $valueObject2 = new TestValueObjectWithDateTimeProperty(new \DateTime('01.01.2013 00:00', new \DateTimeZone('CEST')));
        $valueObject3 = new TestValueObjectWithDateTimeProperty(new \DateTime('01.01.2013 00:00', new \DateTimeZone('GMT')));

        self::assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
        self::assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject3));
    }

    #[Test]
    public function subValueObjectsAreIncludedInTheValueHash(): void
    {
        $subValueObject1 = new TestValueObject('value');
        $subValueObject2 = new TestValueObject('value');
        $subValueObject3 = new TestValueObject('value2');

        $valueObject1 = new TestValueObjectWithSubValueObjectProperties($subValueObject1, 'test');
        $valueObject2 = new TestValueObjectWithSubValueObjectProperties($subValueObject2, 'test');
        $valueObject3 = new TestValueObjectWithSubValueObjectProperties($subValueObject3, 'test');

        self::assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
        self::assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject3));
    }
}
