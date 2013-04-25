<?php
namespace TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Tests\Functional\Persistence\Fixtures as PF;
use Doctrine\ORM\Mapping as ORM;

/**
 * A model fixture which is used for testing the class schema building
 *
 * @Flow\Entity
 */
class EntityWithUseStatements {

	/**
	 * @var SubSubEntity
	 * @ORM\OneToOne
	 */
	protected $subSubEntity;

	/**
	 * @var PF\SubEntity
	 * @ORM\OneToOne
	 */
	protected $propertyFromOtherNamespace;

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SubEntity $parameter
	 * @return void
	 */
	public function fullyQualifiedClassName(SubEntity $parameter) {
	}

	/**
	 * @param PF\SubEntity $parameter
	 * @return void
	 */
	public function aliasedClassName(SubEntity $parameter) {
	}

	/**
	 * @param SubEntity $parameter
	 * @return void
	 */
	public function relativeClassName(SubEntity $parameter) {
	}

	/**
	 * @param float $parameter
	 * @return void
	 */
	public function simpleType($parameter) {
	}
}

?>