<?php
namespace F3\FLOW3\Cache\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A caching backend which forgets everything immediately
 *
 * Used in \F3\FLOW3\Cache\FactoryTest
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class MockBackend extends \F3\FLOW3\Cache\Backend\NullBackend {

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