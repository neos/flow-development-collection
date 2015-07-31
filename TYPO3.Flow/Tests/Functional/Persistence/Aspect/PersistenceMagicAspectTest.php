<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\AnnotatedIdentitiesEntity;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestValueObject;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestValueObjectWithConstructorLogic;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestValueObjectWithConstructorLogicAndInversedOrder;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestValueObjectWithConstructorLogicAndInversedPropertyOrder;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestValueObjectWithDateTimeProperty;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestValueObjectWithSubValueObjectProperties;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestValueObjectWithTransientProperties;

/**
 * Testcase for PersistenceMagicAspect
 *
 */
class PersistenceMagicAspectTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		if (!$this->persistenceManager instanceof \TYPO3\Flow\Persistence\Doctrine\PersistenceManager) {
			$this->markTestSkipped('Doctrine persistence is not enabled');
		}
	}

	/**
	 * @test
	 */
	public function aspectIntroducesUuidIdentifierToEntities() {
		$entity = new AnnotatedIdentitiesEntity();
		$this->assertStringMatchesFormat('%x%x%x%x%x%x%x%x-%x%x%x%x-%x%x%x%x-%x%x%x%x-%x%x%x%x%x%x%x%x', $this->persistenceManager->getIdentifierByObject($entity));
	}

	/**
	 * @test
	 */
	public function aspectDoesNotIntroduceUuidIdentifierToEntitiesWithCustomIdProperties() {
		$entity = new AnnotatedIdEntity();
		$this->assertNull($this->persistenceManager->getIdentifierByObject($entity));
	}

	/**
	 * @test
	 */
	public function aspectFlagsClonedEntities() {
		$entity = new AnnotatedIdEntity();
		$clonedEntity = clone $entity;
		$this->assertObjectNotHasAttribute('Flow_Persistence_clone', $entity);
		$this->assertObjectHasAttribute('Flow_Persistence_clone', $clonedEntity);
		$this->assertTrue($clonedEntity->Flow_Persistence_clone);
	}

	/**
	 * @test
	 */
	public function valueHashIsGeneratedForValueObjects() {
		$valueObject = new TestValueObject('value');

		$this->assertObjectHasAttribute('Persistence_Object_Identifier', $valueObject);
		$this->assertNotEmpty($this->persistenceManager->getIdentifierByObject($valueObject));
	}

	/**
	 * @test
	 * @dataProvider sameValueObjectDataProvider
	 */
	public function valueObjectsWithTheSamePropertyValuesAreEqual($valueObject1, $valueObject2) {
		$this->assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
	}

	public function sameValueObjectDataProvider() {
		return array(
			array(new TestValueObject('value'), new TestValueObject('value')),
			array(new TestValueObjectWithConstructorLogic('val', 'val'), new TestValueObjectWithConstructorLogic(' val', 'val ')),
			array(new TestValueObjectWithConstructorLogic('moreThan5Chars', 'alsoMoreButDoesntMatter'), new TestValueObjectWithConstructorLogic('  moreThan5Chars  ', '        alsoMoreButDoesntMatter '))
		);
	}

	/**
	 * @test
	 * @dataProvider differentValueObjectDataProvider
	 */
	public function valueObjectWithDifferentPropertyValuesAreNotEqual($valueObject1, $valueObject2) {
		$this->assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
	}

	public function differentValueObjectDataProvider() {
		return array(
			array(new TestValueObject('value1'), new TestValueObject('value2')),
			array(new TestValueObject(''), new TestValueObject(NULL)),
			array(new TestValueObjectWithConstructorLogic('chars', ' value2IsJustTrimmed        '), new TestValueObjectWithConstructorLogic('chars ', '        value2IsJustTrimmed '))
		);
	}

	/**
	 * @test
	 */
	public function valueHashMustBeUniqueForEachClassIndependentOfPropertiesOrValues() {
		$valueObject1 = new TestValueObjectWithConstructorLogic('value1', 'value2');
		$valueObject2 = new TestValueObjectWithConstructorLogicAndInversedPropertyOrder('value2', 'value1');

		$this->assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
	}

	/**
	 * @test
	 */
	public function transientPropertiesAreDisregardedForValueHashGeneration() {
		$valueObject1 = new TestValueObjectWithTransientProperties('value1', 'thisDoesntRegardPersistenceWhatSoEver');
		$valueObject2 = new TestValueObjectWithTransientProperties('value1', 'reallyThisPropertyIsTransient');

		$this->assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
	}

	/**
	 * @test
	 */
	public function dateTimeIsDifferentDependingOnTheTimeZone() {
		$valueObject1 = new TestValueObjectWithDateTimeProperty(new \DateTime('01.01.2013 00:00', new \DateTimeZone('GMT')));
		$valueObject2 = new TestValueObjectWithDateTimeProperty(new \DateTime('01.01.2013 00:00', new \DateTimeZone('CEST')));
		$valueObject3 = new TestValueObjectWithDateTimeProperty(new \DateTime('01.01.2013 00:00', new \DateTimeZone('GMT')));

		$this->assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
		$this->assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject3));
	}

	/**
	 * @test
	 */
	public function subValueObjectsAreIncludedInTheValueHash() {
		$subValueObject1 = new TestValueObject('value');
		$subValueObject2 = new TestValueObject('value');
		$subValueObject3 = new TestValueObject('value2');

		$valueObject1 = new TestValueObjectWithSubValueObjectProperties($subValueObject1, 'test');
		$valueObject2 = new TestValueObjectWithSubValueObjectProperties($subValueObject2, 'test');
		$valueObject3 = new TestValueObjectWithSubValueObjectProperties($subValueObject3, 'test');

		$this->assertEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject2));
		$this->assertNotEquals($this->persistenceManager->getIdentifierByObject($valueObject1), $this->persistenceManager->getIdentifierByObject($valueObject3));
	}
}
