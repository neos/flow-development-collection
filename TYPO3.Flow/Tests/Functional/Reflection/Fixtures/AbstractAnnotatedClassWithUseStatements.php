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

use TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model\SuperEntity;

/**
 * An abstract annotated class with use statements
 */
abstract class AbstractAnnotatedClassWithUseStatements {

	/**
	 * @var Model\SubSubEntity
	 */
	protected $subSubEntity;

	/**
	 * @var SuperEntity
	 */
	protected $superEntity;

}
