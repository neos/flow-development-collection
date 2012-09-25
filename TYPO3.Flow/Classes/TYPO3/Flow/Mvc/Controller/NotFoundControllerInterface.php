<?php
namespace TYPO3\Flow\Mvc\Controller;

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
 * Interface for "not found" controllers
 * @deprecated since Flow 1.2. Use the "renderingGroups" options of the exception handler configuration instead
 */
interface NotFoundControllerInterface extends ControllerInterface {

	/**
	 * Sets an exception with technical information about the reason why
	 * no controller could be resolved.
	 *
	 * @param \TYPO3\Flow\Mvc\Controller\Exception $exception
	 * @return void
	 */
	public function setException(\TYPO3\Flow\Mvc\Controller\Exception $exception);

}

?>