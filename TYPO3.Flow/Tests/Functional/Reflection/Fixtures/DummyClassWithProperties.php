<?php
namespace TYPO3\Flow\Tests\Functional\Reflection\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Dummy class for the Reflection tests
 *
 */
class DummyClassWithProperties {

	/**
	 * The @var annotation is intentional as "int" to check if the reflection service normalizes variable types.
	 *
	 * @var int
	 */
	protected $intProperty;

	/**
	 * This should result in the same type string as the "intProperty".
	 *
	 * @var integer
	 */
	protected $integerProperty;

	/**
	 * Same as for int/integer for bool.
	 *
	 * @var bool
	 */
	protected $boolProperty;

	/**
	 * @var boolean
	 */
	protected $booleanProperty;

}
