<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package\MetaData;

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
 * Package person party meta model
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Person extends \F3\FLOW3\Package\MetaData\AbstractParty {

	/**
	 * Company of the person
	 *
	 * @var string
	 */
	protected $company;

	/**
	 * Repository user name of the person
	 *
	 * @var string
	 */
	protected $repositoryUserName;

	/**
	 * Meta data person model constructor
	 *
	 * @param string $role
	 * @param string $name
	 * @param string $email
	 * @param string $website
	 * @param string $company
	 * @param string $repositoryUserName
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function __construct($role, $name, $email = NULL, $website = NULL, $company = NULL, $repositoryUserName = NULL) {
		parent::__construct($role, $name, $email, $website);

		$this->company = $company;
		$this->repositoryUserName = $repositoryUserName;
	}

	/**
	 * @return string The company of the person
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getCompany() {
		return $this->company;
	}

	/**
	 * @return string The repository username
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getRepositoryUserName() {
		return $this->repositoryUserName;
	}

	/**
	 * @return string Party type "person"
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getPartyType() {
		return \F3\FLOW3\Package\MetaData::PARTY_TYPE_PERSON;
	}
}
?>