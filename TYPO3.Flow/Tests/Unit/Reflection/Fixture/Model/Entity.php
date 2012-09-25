<?php
namespace TYPO3\Flow\Tests\Reflection\Fixture\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A model fixture which is used for testing the class schema building
 *
 * @Flow\Entity
 */
class Entity {

	/**
	 * An identity property
	 *
	 * @var string
	 * @Flow\Identity
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
	 * @Flow\Identity
	 */
	protected $someDate;

	/**
	 * @var \SplObjectStorage
	 * @Flow\Lazy
	 */
	protected $someSplObjectStorage;

	/**
	 * A transient string
	 *
	 * @var string
	 * @Flow\Transient
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