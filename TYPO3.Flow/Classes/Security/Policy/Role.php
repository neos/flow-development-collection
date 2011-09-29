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

/**
 * A role for the PolicyService. These roles can be structured in a tree.
 *
 * @scope prototype
 * @entity
 */
class Role {

	/**
	 * The string identifier of this role
	 *
	 * @var string
	 * @identity
	 * @Id
	 */
	protected $identifier;

	/**
	 * Constructor.
	 *
	 * @param string $identifier The string identifier of this role
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __toString() {
		return $this->identifier;
	}
}

?>