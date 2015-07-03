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
use TYPO3\Flow\Configuration\ConfigurationManager;

/**
 * Used to enable property injection for configuration including settings.
 *
 * Flow will build Dependency Injection code for the property and try
 * to inject the configured configuration.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class InjectConfiguration {

	/**
	 * Path of a configuration which should be injected into the property.
	 * Can be specified as anonymous argument: InjectConfiguration("some.path")
	 *
	 * For type "Settings" this refers to the relative path (excluding the package key)
	 *
	 * Example: session.name
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Defines the package key to be used for retrieving settings. If no package key is specified, we'll assume the
	 * package to be the same which contains the class where the InjectConfiguration annotation is used.
	 *
	 * Note: This property is only supported for type "Settings"
	 *
	 * Example: TYPO3.Flow
	 *
	 * @var string
	 */
	public $package;

	/**
	 * Type of Configuration (defaults to "Settings").
	 *
	 * @var string one of the ConfigurationManager::CONFIGURATION_TYPE_* constants
	 */
	public $type = ConfigurationManager::CONFIGURATION_TYPE_SETTINGS;

	/**
	 * @param array $values
	 */
	public function __construct(array $values) {
		if (isset($values['value']) || isset($values['path'])) {
			$this->path = isset($values['path']) ? (string)$values['path'] : (string)$values['value'];
		}
		if (isset($values['package'])) {
			$this->package = (string)$values['package'];
		}
		if (isset($values['type'])) {
			$this->type = (string)$values['type'];
		}
	}
}
