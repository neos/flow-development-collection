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
 * Used to set the scope of an object.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Scope {

	/**
	 * The scope of an object: prototype, singleton, session. (Usually given as anonymous argument.)
	 * @var string
	 */
	public $value = 'prototype';

	/**
	 * @param array $values
	 */
	public function __construct(array $values) {
		if (isset($values['value'])) {
			$this->value = $values['value'];
		}
	}

}

?>