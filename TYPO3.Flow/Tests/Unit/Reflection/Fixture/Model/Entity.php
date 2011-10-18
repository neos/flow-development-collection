<?php
namespace TYPO3\FLOW3\Tests\Reflection\Fixture\Model;

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
 * A model fixture which is used for testing the class schema building
 *
 * @FLOW3\Entity
 */
class Entity {

	/**
	 * An identity property
	 *
	 * @var string
	 * @FLOW3\Identity
	 */
	protected $someIdentifier;

	/**
	 * Just a normal string
	 *
	 * @var string
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
	 * @FLOW3\Identity
	 */
	protected $someDate;

	/**
	 * @var \SplObjectStorage
	 * @FLOW3\Lazy
	 */
	protected $someSplObjectStorage;

	/**
	 * A transient string
	 *
	 * @var string
	 * @FLOW3\Transient
	 */
	protected $someTransientString;

	/**
	 * @var boolean
	 */
	protected $someBoolean;

	/**
	 * Just an empty constructor
	 *
	 */
	public function __construct() {
	}

	/**
	 * Just a dummy method
	 *
	 * @return void
	 */
	public function someDummyMethod() {
	}

}

?>