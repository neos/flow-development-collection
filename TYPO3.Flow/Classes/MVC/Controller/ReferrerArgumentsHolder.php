<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * A holder class for all reference arguments.
 *
 * This is only a crux to pass arguments into the ObjectSerializer. As soon
 * as we can use serialize, we can drop this class.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class ReferrerArgumentsHolder {

	/**
	 * @var array
	 */
	protected $referrerArguments;

	/**
	 * @param array $referrerArguments
	 */
	public function __construct(array $referrerArguments) {
		$this->referrerArguments = $referrerArguments;
	}

	/**
	 * @return array
	 */
	public function getReferrerArguments() {
		return $this->referrerArguments;
	}

}

?>