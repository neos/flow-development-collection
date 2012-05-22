<?php
namespace TYPO3\FLOW3\Tests\Functional\Object\Fixtures;

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
 * A class of scope prototype (but without explicit scope annotation)
 */
class PrototypeClassDsub extends PrototypeClassD {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassD
	 */
	protected $objectD;

}
?>