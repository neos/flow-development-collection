<?php
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

use Neos\Flow\Persistence\Generic\PersistenceManager;
use Neos\Flow\Tests\Functional\Persistence\Fixtures;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for PersistenceMagicAspect
 */
class PersistenceMagicAspectTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
    }

    /**
     * @test
     */
    public function aspectIntroducesUuidIdentifierToEntities()
    {
        $entity = new Fixtures\AnnotatedIdentitiesEntity();
        $this->assertStringMatchesFormat('%x%x%x%x%x%x%x%x-%x%x%x%x-%x%x%x%x-%x%x%x%x-%x%x%x%x%x%x%x%x', $this->persistenceManager->getIdentifierByObject($entity));
    }

    /**
     * @test
     */
    public function aspectDoesNotIntroduceUuidIdentifierToEntitiesWithCustomIdProperties()
    {
        $entity = new Fixtures\AnnotatedIdEntity();
        $this->assertNull($this->persistenceManager->getIdentifierByObject($entity));
    }

    /**
     * @test
     */
    public function aspectFlagsClonedEntities()
    {
        $entity = new Fixtures\AnnotatedIdEntity();
        $clonedEntity = clone $entity;
        $this->assertObjectNotHasAttribute('Flow_Persistence_clone', $entity);
        $this->assertObjectHasAttribute('Flow_Persistence_clone', $clonedEntity);
        $this->assertTrue($clonedEntity->Flow_Persistence_clone);
    }

    /**
     * @test
     */
    public function valueHashIsGeneratedForValueObjects()
    {
        $valueObject = new Fixtures\TestValueObject('value');

        $this->assertObjectHasAttribute('Persistence_Object_Identifier', $valueObject);
        $this->assertNotEmpty($this->persistenceManager->getIdentifierByObject($valueObject));
    }

    /**
     * @test
     * @dataProvider sameValueObjectDataProvider
     */
    public function valueObjectsWithTheSamePropertyValuesAreEqual($valueObject1, $valueObject2)
    {
        $this->assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
    }

    public function sameValueObjectDataProvider()
    {
        return [
            [new Fixtures\TestValueObject('value'), new Fixtures\TestValueObject('value')],
            [new Fixtures\TestValueObjectWithConstructorLogic('val', 'val'), new Fixtures\TestValueObjectWithConstructorLogic(' val', 'val ')],
            [new Fixtures\TestValueObjectWithConstructorLogic('moreThan5Chars', 'alsoMoreButDoesntMatter'), new Fixtures\TestValueObjectWithConstructorLogic('  moreThan5Chars  ', '        alsoMoreButDoesntMatter ')]
        ];
    }

    /**
     * @test
     * @dataProvider differentValueObjectDataProvider
     */
    public function valueObjectWithDifferentPropertyValuesAreNotEqual($valueObject1, $valueObject2)
    {
        $this->assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
    }

    public function differentValueObjectDataProvider()
    {
        return [
            [new Fixtures\TestValueObject('value1'), new Fixtures\TestValueObject('value2')],
            [new Fixtures\TestValueObject(''), new Fixtures\TestValueObject(null)],
            [new Fixtures\TestValueObjectWithConstructorLogic('chars', ' value2IsJustTrimmed        '), new Fixtures\TestValueObjectWithConstructorLogic('chars ', '        value2IsJustTrimmed ')]
        ];
    }

    /**
     * @test
     */
    public function valueHashMustBeUniqueForEachClassIndependentOfPropertiesOrValues()
    {
        $valueObject1 = new Fixtures\TestValueObjectWithConstructorLogic('value1', 'value2');
        $valueObject2 = new Fixtures\TestValueObjectWithConstructorLogicAndInversedPropertyOrder('value2', 'value1');

        $this->assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
    }

    /**
     * @test
     */
    public function transientPropertiesAreDisregardedForValueHashGeneration()
    {
        $valueObject1 = new Fixtures\TestValueObjectWithTransientProperties('value1', 'thisDoesntRegardPersistenceWhatSoEver');
        $valueObject2 = new Fixtures\TestValueObjectWithTransientProperties('value1', 'reallyThisPropertyIsTransient');

        $this->assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
    }

    /**
     * @test
     */
    public function dateTimeIsDifferentDependingOnTheTimeZone()
    {
        $valueObject1 = new Fixtures\TestValueObjectWithDateTimeProperty(new \DateTime('01.01.2013 00:00', new \DateTimeZone('GMT')));
        $valueObject2 = new Fixtures\TestValueObjectWithDateTimeProperty(new \DateTime('01.01.2013 00:00', new \DateTimeZone('CEST')));
        $valueObject3 = new Fixtures\TestValueObjectWithDateTimeProperty(new \DateTime('01.01.2013 00:00', new \DateTimeZone('GMT')));

        $this->assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
        $this->assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject3));
    }

    /**
     * @test
     */
    public function subValueObjectsAreIncludedInTheValueHash()
    {
        $subValueObject1 = new Fixtures\TestValueObject('value');
        $subValueObject2 = new Fixtures\TestValueObject('value');
        $subValueObject3 = new Fixtures\TestValueObject('value2');

        $valueObject1 = new Fixtures\TestValueObjectWithSubValueObjectProperties($subValueObject1, 'test');
        $valueObject2 = new Fixtures\TestValueObjectWithSubValueObjectProperties($subValueObject2, 'test');
        $valueObject3 = new Fixtures\TestValueObjectWithSubValueObjectProperties($subValueObject3, 'test');

        $this->assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
        $this->assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject3));
    }
}
