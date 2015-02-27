<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

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
 * A class which has lazy dependencies
 */
class ClassWithLazyDependencies {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA
	 */
	public $lazyA;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB
	 */
	public $lazyB;

	/**
	 * @Flow\Inject(lazy = FALSE)
	 * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassC
	 */
	public $eagerC;

}
