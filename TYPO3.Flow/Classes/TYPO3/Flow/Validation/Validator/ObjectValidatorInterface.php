<?php
namespace TYPO3\Flow\Validation\Validator;

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
 * Contract for a validator
 *
 * @api
 */
interface ObjectValidatorInterface extends ValidatorInterface {

	/**
	 * Allows to set a container to keep track of validated instances.
	 *
	 * @param \SplObjectStorage $validatedInstancesContainer A container to keep track of validated instances
	 * @return void
	 * @api
	 */
	public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer);

}
?>