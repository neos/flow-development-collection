<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A simple entity for persistence tests
 *
 * @FLOW3\Entity
 * @ORM\Table(name="persistence_testentity")
 */
class TestEntity {

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 * @FLOW3\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity
	 * @ORM\ManyToOne
	 */
	protected $relatedEntity;

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestValueObject
	 * @ORM\ManyToOne
	 */
	protected $relatedValueObject;

	/**
	 * @var string
	 * @FLOW3\Validate(type="StringLength", options={"minimum"=3})
	 */
	protected $name = '';

	/**
	 * @var string
	 * @FLOW3\Validate(type="NotEmpty", validationGroups={"SomeOther"})
	 */
	protected $description = 'This is some text';

	/**
	 * @var array
	 */
	protected $arrayProperty = array();

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * @param array $arrayProperty
	 * @return void
	 */
	public function setArrayProperty($arrayProperty) {
		$this->arrayProperty = $arrayProperty;
	}

	/**
	 * @return array
	 */
	public function getArrayProperty() {
		return $this->arrayProperty;
	}

	/**
	 * @return string
	 */
	public function sayHello() {
		return 'Hello';
	}

	/**
	 * @param \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity $relatedEntities
	 */
	public function setRelatedEntity(\TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity $relatedEntity) {
		$this->relatedEntity = $relatedEntity;
	}

	/**
	 * @return \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity
	 */
	public function getRelatedEntity() {
		return $this->relatedEntity;
	}

	/**
	 * @return \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	public function getObjectManager() {
		return $this->objectManager;
	}

	/**
	 * @param \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestValueObject $relatedValueObject
	 * @return void
	 */
	public function setRelatedValueObject($relatedValueObject) {
		$this->relatedValueObject = $relatedValueObject;
	}

	/**
	 * @return \TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestValueObject
	 */
	public function getRelatedValueObject() {
		return $this->relatedValueObject;
	}
}
?>