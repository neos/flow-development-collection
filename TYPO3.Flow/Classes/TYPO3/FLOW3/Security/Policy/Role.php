<?php
namespace TYPO3\FLOW3\Security\Policy;

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
 * A role for the PolicyService. These roles can be structured in a tree.
 *
 * @FLOW3\Entity
 */
class Role {

	/**
	 * The string identifier of this role
	 *
	 * @var string
	 * @FLOW3\Identity
	 * @ORM\Id
	 */
	protected $identifier;

	/**
	 * Constructor.
	 *
	 * @param string $identifier The string identifier of this role
	 * @throws \RuntimeException
	 */
	public function __construct($identifier) {
		if (!is_string($identifier)) {
			throw new \RuntimeException('Role identifier must be a string, "' . gettype($identifier) .'" given.', 1296509556);
		}
		$this->identifier = $identifier;
	}

	/**
	 * Returns the string representation of this role (the identifier)
	 *
	 * @return string the string representation of this role
	 */
	public function __toString() {
		return $this->identifier;
	}
}

?>