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
 * Used to enable property injection for settings.
 *
 * Flow will build Dependency Injection code for the property and try
 * to inject the configured setting.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class InjectSettings {

	/**
	 * Path of a setting (without the package key) which should be injected into the property.
	 *
	 * Example: security.enable
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Defines the package key to be used for retrieving a setting specified via the "package" parameter. If no package key
	 * is specified, we'll assume the package to be the same which contains the class where the Inject annotation is used.
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
		if (isset($values['path'])) {
			$this->path = (string)$values['path'];
		}
		if (isset($values['package'])) {
			$this->package = (string)$values['package'];
		}
	}

}
