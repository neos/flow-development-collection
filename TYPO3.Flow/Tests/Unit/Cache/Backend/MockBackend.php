<?php
namespace TYPO3\FLOW3\Cache\Backend;

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
 * A caching backend which forgets everything immediately
 *
 * Used in \TYPO3\FLOW3\Cache\FactoryTest
 *
 * @FLOW3\Scope("prototype")
 */
class MockBackend extends \TYPO3\FLOW3\Cache\Backend\NullBackend {

	/**
	 * @var mixed
	 */
	protected $someOption;

	/**
	 * Sets some option
	 *
	 * @param mixed $value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSomeOption($value) {
		$this->someOption = $value;
	}

	/**
	 * Returns the option value
	 *
	 * @return mixed
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSomeOption() {
		return $this->someOption;
	}
}
?>