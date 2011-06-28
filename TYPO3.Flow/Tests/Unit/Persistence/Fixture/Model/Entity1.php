<?php
namespace TYPO3\FLOW3\Tests\Persistence\Fixture\Model;

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
 * A model fixture which is used for testing the class schema builder
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @entity
 */
class Entity1 {

	/**
	 * An identifier property
	 *
	 * @var string
	 * @uuid
	 */
	protected $someIdentifier;

	/**
	 * Just a normal string
	 *
	 * @var string
	 * @identity
	 */
	protected $someString;

	/**
	 * @var integer
	 */
	protected $someInteger;

	/**
	 * @var float
	 */
	protected $someFloat;

	/**
	 * @var \DateTime
	 * @identity
	 */
	protected $someDate;

	/**
	 * @var \SplObjectStorage
	 * @lazy
	 */
	protected $someSplObjectStorage;

	/**
	 * A transient string
	 *
	 * @var string
	 * @transient
	 */
	protected $someTransientString;

	/**
	 * @var boolean
	 */
	protected $someBoolean;

	/**
	 * Just an empty constructor
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {

	}

	/**
	 * Just a dummy method
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function someDummyMethod() {

	}
}
?>