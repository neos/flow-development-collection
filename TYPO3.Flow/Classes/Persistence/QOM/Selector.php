<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\QOM;

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
 * Selects a subset of the objects in the persistence layer based on object type.
 *
 * A selector selects every node in the repository, subject to access control
 * constraints, that is instanceof $className
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Selector {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var string
	 */
	protected $selectorName;

	/**
	 * Constructs the Selector instance
	 *
	 * @param string $selectorName
	 * @param string $className
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($selectorName, $className) {
		$this->selectorName = $selectorName;
		$this->className = $className;
	}

	/**
	 * Gets the name of the required class.
	 *
	 * @return string the node type name; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Gets the selector name.
	 * A selector's name can be used elsewhere in the query to identify the selector.
	 *
	 * @return string the selector name; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getSelectorName() {
		return $this->selectorName;
	}

}

?>