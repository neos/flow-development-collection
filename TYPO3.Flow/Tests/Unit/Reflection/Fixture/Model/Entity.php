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

/**
 * A model fixture which is used for testing the class schema building
 *
 * @entity
 */
class Entity {

	/**
	 * An identifier property
	 *
	 * @var string
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