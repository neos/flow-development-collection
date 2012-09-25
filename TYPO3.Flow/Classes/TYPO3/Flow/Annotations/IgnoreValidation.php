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
 * Used to ignore validation on a specific method argument.
 *
 * @Annotation
 * @Target("METHOD")
 */
final class IgnoreValidation {

	/**
	 * Name of the argument to skip validation for. (Can be given as anonymous argument.)
	 * @var string
	 */
	public $argumentName;

	/**
	 * @param array $values
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $values) {
		if (!isset($values['value']) && !isset($values['argumentName'])) {
			throw new \InvalidArgumentException('An IgnoreValidation annotation must be given an argument name.', 1318456607);
		}
		$this->argumentName = ltrim(isset($values['argumentName']) ? $values['argumentName'] : $values['value'], '$');
	}

}

?>