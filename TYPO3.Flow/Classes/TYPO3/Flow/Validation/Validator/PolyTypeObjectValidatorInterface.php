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
 * Contract for a poly type validator, able to act on possibly any type.
 *
 * @api
 */
interface PolyTypeObjectValidatorInterface extends ObjectValidatorInterface {

	/**
	 * Checks the given target can be validated by the validator implementation.
	 *
	 * @param mixed $target The object or class name to be checked
	 * @return boolean TRUE if the target can be validated
	 * @api
	 */
	public function canValidate($target);

	/**
	 * Return the priority of this validator.
	 *
	 * Validators with a high priority are chosen before low priority and only one
	 * of multiple capable validators will be used.
	 *
	 * @return integer
	 * @api
	 */
	public function getPriority();

}
?>