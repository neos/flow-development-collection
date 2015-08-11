<?php
namespace TYPO3\Flow\Property;

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
 * An generic Property related exception
 *
 * @api
 */
class Exception extends \TYPO3\Flow\Exception {

	/**
	 * Return the status code of the nested exception, if any.
	 *
	 * @return integer
	 */
	public function getStatusCode() {
		$nestedException = $this->getPrevious();
		if ($nestedException !== NULL && $nestedException instanceof \TYPO3\Flow\Exception) {
			return $nestedException->getStatusCode();
		}
		return parent::getStatusCode();
	}
}
