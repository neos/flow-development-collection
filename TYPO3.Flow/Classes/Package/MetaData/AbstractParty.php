<?php
namespace TYPO3\FLOW3\Package\MetaData;

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
 * Party meta model for persons and companies
 *
 */
abstract class AbstractParty {

	/**
	 * The party role
	 *
	 * @var string
	 */
	protected $role;

	/**
	 * Name of the party
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Email of the party
	 *
	 * @var string
	 */
	protected $email;

	/**
	 * Website of the party
	 *
	 * @var string
	 */
	protected $website;

	/**
	 * Meta data party model constructor
	 *
	 * @param string $role
	 * @param string $name
	 * @param string $email
	 * @param string $website
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function __construct($role, $name, $email = NULL, $website = NULL) {
		$this->role = $role;
		$this->name = $name;
		$this->email = $email;
		$this->website = $website;
	}

	/**
	 * @return string The role of the party
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getRole() {
		return $this->role;
	}

	/**
	 * @return string The name of the party
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string The email of the party
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @return string The website of the party
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getWebsite() {
		return $this->website;
	}

	/**
	 * Get the party type (MetaData\PARTY_TYPE_PERSON, MetaData\PARTY_TYPE_COMPANY)
	 *
	 * @return string The type of the party (person, company)
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	abstract public function getPartyType();
}
?>