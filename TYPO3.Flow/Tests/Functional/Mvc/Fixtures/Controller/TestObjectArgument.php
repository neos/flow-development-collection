<?php
namespace TYPO3\Flow\Tests\Functional\Mvc\Fixtures\Controller;

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

/**
 * An object argument with validation
 */
class TestObjectArgument {

	/**
	 * @var string
	 * @Flow\Validate(type="NotEmpty")
	 */
	protected $name;

	/**
	 * @var string
	 * @Flow\Validate(type="EmailAddress",validationGroups={"Controller","Default","validatedGroup"})
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