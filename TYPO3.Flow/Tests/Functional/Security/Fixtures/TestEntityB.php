<?php
namespace TYPO3\Flow\Tests\Functional\Security\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * An entity for tests
 *
 * @Flow\Entity
 */
class TestEntityB {

	/**
	 * @var string
	 */
	protected $stringValue;

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityA
	 * @ORM\OneToOne(mappedBy="relatedEntityB")
	 */
	protected $relatedEntityA;

	/**
	 * @var \TYPO3\Flow\Security\Account
	 * @ORM\ManyToOne
	 */
	protected $ownerAccount;

	/**
	 * Constructor
	 *
	 * @param string $stringValue
	 */
	public function __construct($stringValue) {
		$this->stringValue = $stringValue;
	}

	/**
	 * @param string $stringValue
	 */
	public function setStringValue($stringValue) {
		$this->stringValue = $stringValue;
	}

	/**
	 * @return string
	 */
	public function getStringValue() {
		return $this->stringValue;
	}

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityA $relatedEntityA
	 */
	public function setRelatedEntityA($relatedEntityA) {
		$this->relatedEntityA = $relatedEntityA;
	}

	/**
	 * @return \TYPO3\Flow\Tests\Functional\Security\Fixtures\TestEntityA
	 */
	public function getRelatedEntityA() {
		return $this->relatedEntityA;
	}

	/**
	 * @param \TYPO3\Flow\Security\Account $ownerAccount
	 */
	public function setOwnerAccount($ownerAccount) {
		$this->ownerAccount = $ownerAccount;
	}

	/**
	 * @return \TYPO3\Flow\Security\Account
	 */
	public function getOwnerAccount() {
		return $this->ownerAccount;
	}
}
