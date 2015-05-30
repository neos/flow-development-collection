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
 * A class to serialize and check if all dependencies are reinjected on unserialize.
 */
class ClassToBeSerialized {

	/**
	 * @var string
	 */
	public $someProperty = 'I am not a coffee machine.';

	/**
	 * @var string
	 */
	protected $protectedProperty = 'I am protected.';

	/**
	 * @var string
	 */
	private $privateProperty = 'Saving Private Ryan.';

	/**
	 * @Flow\Inject(lazy = FALSE)
	 * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassAishInterface
	 */
	public $interfaceDeclaredSingletonButImplementationIsPrototype;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB
	 */
	public $lazyB;

	/**
	 * @Flow\Inject(lazy = FALSE)
	 * @var SingletonClassC
	 */
	public $eagerC;

	/**
	 * @Flow\Inject(lazy = FALSE)
	 * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassB
	 */
	public $prototypeB;

}
