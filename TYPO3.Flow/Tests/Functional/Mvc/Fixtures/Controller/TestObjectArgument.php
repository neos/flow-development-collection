<?php
namespace TYPO3\FLOW3\Tests\Functional\Mvc\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An object argument with validation
 */
class TestObjectArgument {

	/**
	 * @var string
	 * @FLOW3\Validate(type="NotEmpty")
	 */
	protected $name;

	/**
	 * @var string
	 * @FLOW3\Validate(type="EmailAddress",validationGroups={"Controller","Default","validatedGroup"})
	 */
	protected $emailAddress;

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
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
	public function getEmailAddress() {
		return $this->emailAddress;
	}

	/**
	 * @param string $emailAddress
	 */
	public function setEmailAddress($emailAddress) {
		$this->emailAddress = $emailAddress;
	}

}
?>