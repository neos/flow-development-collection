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
 * Used to disable proxy building for an object.
 *
 * If disabled, neither Dependency Injection nor AOP can be used
 * on the object.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Proxy {

	/**
	 * Whether proxy building for the target is disabled. (Can be given as anonymous argument.)
	 * @var boolean
	 */
	public $enabled = TRUE;

	/**
	 * @param array $values
	 */
	public function __construct(array $values) {
		if ($values['value'] !== array()) {
			$this->enabled = isset($values['enabled']) ? (boolean)$values['enabled'] : (boolean)$values['value'];
		}
	}

}
