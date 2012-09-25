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
 * A restrictable entity for tests
 *
 * @Flow\Entity
 */
class RestrictableEntity {

	/**
	 * @var boolean
	 */
	protected $hidden = FALSE;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var \TYPO3\Flow\Security\Account
	 * @ORM\ManyToOne
	 */
	protected $ownerAccount;

	/**
	 * Constructor
	 *
	 * @param string $name The name of the entity
	 * @return void
	 */
	public function __construct($name) {
		$this->name = $name;
	}

	/**
	 * @return boolean Returns TRUE, if this entity is hidden
	 */
	public function isHidden() {
		return $this->hidden;
	}

	/**
	 * @param boolean $hidden
	 */
	public function setHidden($hidden) {
		$this->hidden = $hidden;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
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
?>