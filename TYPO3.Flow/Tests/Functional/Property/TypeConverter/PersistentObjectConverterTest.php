<?php
namespace TYPO3\Flow\Tests\Functional\Property\TypeConverter;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Flow\Tests\Functional\Property\Fixtures;
use TYPO3\Flow\Property\Exception as PropertyException;

class PersistentObjectConverterTest extends FunctionalTestCase
{
    /**
     *
     * @var PropertyMapper
     */
    protected $propertyMapper;

    protected $sourceProperties = [
        'name' => 'Christian M',
        'age' => '34',
        'averageNumberOfKids' => '0'
    ];

    protected static $testablePersistenceEnabled = true;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->propertyMapper = $this->objectManager->get(PropertyMapper::class);
    }

    /**
     * @test
     */
    public function entityWithImmutablePropertyIsCreatedCorrectly()
    {
        $result = $this->propertyMapper->convert($this->sourceProperties, Fixtures\TestEntityWithImmutableProperty::class);
        $this->assertInstanceOf(Fixtures\TestEntityWithImmutableProperty::class, $result);
        $this->assertEquals('Christian M', $result->getName());
    }

    /**
     * @test
     */
    public function entityWithImmutablePropertyCanBeUpdatedIfImmutablePropertyIsNotGiven()
    {
        $result = $this->propertyMapper->convert($this->sourceProperties, Fixtures\TestEntityWithImmutableProperty::class);
        $identifier = $this->persistenceManager->getIdentifierByObject($result);
        $this->persistenceManager->add($result);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $update = [
            '__identity' => $identifier,
            'age' => '25'
        ];

        $result = $this->propertyMapper->convert($update, Fixtures\TestEntityWithImmutableProperty::class);

        $this->assertInstanceOf(Fixtures\TestEntityWithImmutableProperty::class, $result);
        $this->assertEquals('Christian M', $result->getName());
    }

    /**
     * @test
     */
    public function entityWithImmutablePropertyCanBeUpdatedIfImmutablePropertyIsGivenAndSameAsBefore()
    {
        $result = $this->propertyMapper->convert($this->sourceProperties, Fixtures\TestEntityWithImmutableProperty::class);
        $identifier = $this->persistenceManager->getIdentifierByObject($result);
        $this->persistenceManager->add($result);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $update = [
            '__identity' => $identifier,
            'age' => '25',
            'name' => 'Christian M'
        ];

        $result = $this->propertyMapper->convert($update, Fixtures\TestEntityWithImmutableProperty::class);

        $this->assertInstanceOf(Fixtures\TestEntityWithImmutableProperty::class, $result);
        $this->assertEquals('Christian M', $result->getName());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Property\Exception
     */
    public function entityWithImmutablePropertyCanNotBeUpdatedWhenImmutablePropertyChanged()
    {
        $result = $this->propertyMapper->convert($this->sourceProperties, Fixtures\TestEntityWithImmutableProperty::class);
        $identifier = $this->persistenceManager->getIdentifierByObject($result);
        $this->persistenceManager->add($result);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        $update = [
            '__identity' => $identifier,
            'age' => '25',
            'name' => 'Christian D'
        ];

        $result = $this->propertyMapper->convert($update, Fixtures\TestEntityWithImmutableProperty::class);

        $this->assertInstanceOf(Fixtures\TestEntityWithImmutableProperty::class, $result);
        $this->assertEquals('Christian M', $result->getName());
    }
}
