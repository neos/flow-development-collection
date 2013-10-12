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
 * A class of scope prototype (but without explicit scope annotation)
 */
class PrototypeClassF {

	/**
	 * @Flow\Transient
	 * @var string
	 */
	protected $transientProperty;

	/**
	 * @var string
	 */
	protected $nonTransientProperty;

	/**
	 * @param string $transientProperty
	 */
	public function setTransientProperty($transientProperty) {
		$this->transientProperty = $transientProperty;
	}

	/**
	 * @return string
	 */
	public function getTransientProperty() {
		return $this->transientProperty;
	}

	/**
	 * @param string $nonTransientProperty
	 */
	public function setNonTransientProperty($nonTransientProperty) {
		$this->nonTransientProperty = $nonTransientProperty;
	}

	/**
	 * @return string
	 */
	public function getNonTransientProperty() {
		return $this->nonTransientProperty;
	}

}
