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
 * Used to enable property injection.
 *
 * Flow will build Dependency Injection code for the property and try
 * to inject a value as specified by the var annotation.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Inject {

	/**
	 * Whether the dependency should be injected instantly or if a lazy dependency
	 * proxy should be injected instead
	 *
	 * @var boolean
	 */
	public $lazy = TRUE;

	/**
	 * Path of a setting (without the package key) which should be injected into the property.
	 * Example: security.enable
	 *
	 * @var string
	 */
	public $setting;

	/**
	 * Defines the package to be used for retrieving a setting specified via the "setting" parameter. If no package
	 * is specified, we'll assume the package to be the same which contains the class where the Inject annotation is
	 * used.
	 *
	 * Example: TYPO3.Flow
	 *
	 * @var string
	 */
	public $package;

	/**
	 * @param array $values
	 */
	public function __construct(array $values) {
		if (isset($values['lazy'])) {
			$this->lazy = (boolean)$values['lazy'];
		}
		if (isset($values['setting'])) {
			$this->setting = (string)$values['setting'];
		}
		if (isset($values['package'])) {
			$this->package = (string)$values['package'];
		}
	}

}
