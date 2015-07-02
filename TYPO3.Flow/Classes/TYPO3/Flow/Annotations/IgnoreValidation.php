<?php
namespace TYPO3\Flow\Annotations;

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
 * Used to ignore validation on a specific method argument or class property.
 *
 * By default no validation will be executed for the given argument. To gather validation results for further
 * processing, the "evaluate" option can be set to true (while still ignoring any validation error).
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 */
final class IgnoreValidation {

	/**
	 * Name of the argument to skip validation for. (Can be given as anonymous argument.)
	 * @var string
	 */
	public $argumentName;

	/**
	 * Whether to evaluate the validation results of the argument
	 * @var boolean
	 */
	public $evaluate = FALSE;

	/**
	 * @param array $values
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $values) {
		if (isset($values['value']) || isset($values['argumentName'])) {
			$this->argumentName = ltrim(isset($values['argumentName']) ? $values['argumentName'] : $values['value'], '$');
		}

		if (isset($values['evaluate'])) {
			$this->evaluate = (boolean)$values['evaluate'];
		}
	}

}
