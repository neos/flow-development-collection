<?php
namespace TYPO3\Flow\Tests\Functional\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\FunctionalTestCase;

class PersistentObjectConverterTest extends FunctionalTestCase {

	/**
	 *
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	protected $sourceProperties = array(
		'name' => 'Christian M',
		'age' => '34',
		'averageNumberOfKids' => '0'
	);

	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->propertyMapper = $this->objectManager->get('TYPO3\Flow\Property\PropertyMapper');
	}

	/**
	 * @test
	 */
	public function entityWithImmutablePropertyIsCreatedCorrectly() {
		$result = $this->propertyMapper->convert($this->sourceProperties, 'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestEntityWithImmutableProperty');
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Property\Fixtures\TestEntityWithImmutableProperty', $result);
		$this->assertEquals('Christian M', $result->getName());
	}

	/**
	 * @test
	 */
	public function entityWithImmutablePropertyCanBeUpdatedIfImmutablePropertyIsNotGiven() {
		$result = $this->propertyMapper->convert($this->sourceProperties, 'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestEntityWithImmutableProperty');
		$identifier = $this->persistenceManager->getIdentifierByObject($result);
		$this->persistenceManager->add($result);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$update = array(
			'__identity' => $identifier,
			'age' => '25'
		);

		$result = $this->propertyMapper->convert($update, 'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestEntityWithImmutableProperty');

		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Property\Fixtures\TestEntityWithImmutableProperty', $result);
		$this->assertEquals('Christian M', $result->getName());
	}

	/**
	 * @test
	 */
	public function entityWithImmutablePropertyCanBeUpdatedIfImmutablePropertyIsGivenAndSameAsBefore() {
		$result = $this->propertyMapper->convert($this->sourceProperties, 'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestEntityWithImmutableProperty');
		$identifier = $this->persistenceManager->getIdentifierByObject($result);
		$this->persistenceManager->add($result);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$update = array(
			'__identity' => $identifier,
			'age' => '25',
			'name' => 'Christian M'
		);

		$result = $this->propertyMapper->convert($update, 'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestEntityWithImmutableProperty');

		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Property\Fixtures\TestEntityWithImmutableProperty', $result);
		$this->assertEquals('Christian M', $result->getName());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Property\Exception
	 */
	public function entityWithImmutablePropertyCanNotBeUpdatedWhenImmutablePropertyChanged() {
		$result = $this->propertyMapper->convert($this->sourceProperties, 'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestEntityWithImmutableProperty');
		$identifier = $this->persistenceManager->getIdentifierByObject($result);
		$this->persistenceManager->add($result);
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$update = array(
			'__identity' => $identifier,
			'age' => '25',
			'name' => 'Christian D'
		);

		$result = $this->propertyMapper->convert($update, 'TYPO3\Flow\Tests\Functional\Property\Fixtures\TestEntityWithImmutableProperty');

		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Property\Fixtures\TestEntityWithImmutableProperty', $result);
		$this->assertEquals('Christian M', $result->getName());
	}
}