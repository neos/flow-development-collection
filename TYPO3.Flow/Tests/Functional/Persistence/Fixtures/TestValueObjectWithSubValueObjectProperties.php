<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A simple value object for persistence tests
 *
 * @Flow\ValueObject
 * @ORM\Table(name="persistence_testvalueobjectwithsubvalueobjectproperties")
 */
class TestValueObjectWithSubValueObjectProperties {

	/**
	 * @var TestValueObject
	 */
	protected $value1;

	/**
	 * @var string
	 */
	protected $value2;

	/**
	 * @param TestValueObject $value1
	 * @param string $value2
	 */
	public function __construct(TestValueObject $value1, $value2) {
		$this->value1 = $value1;
		$this->value2 = trim($value2);
	}
}
?>