<?php
namespace TYPO3\FLOW3\Annotations;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Proxy {

	/**
	 * Whether proxy building for the target is disabled.
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

?>