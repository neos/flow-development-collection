<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 */

/**
 * A caching backend which forgets everything immediately
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_Cache_MockBackend extends F3_FLOW3_Cache_Backend_Null {

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