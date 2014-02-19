<?php
namespace TYPO3\Flow\Configuration;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Error\Result;

/**
 * A validator for all configuration entries using Schema
 *
 * Writing Custom Schemata
 * =======================
 *
 * The schemas are searched in the path "Resources/Private/Schema" of all
 * active packages. The schema-filenames must match the pattern
 * [type].[path].schema.yaml. The type and/or the path can also be
 * expressed as subdirectories of Resources/Private/Schema. So
 * Settings/TYPO3/Flow.persistence.schema.yaml will match the same paths
 * like Settings.TYPO3.Flow.persistence.schema.yaml or
 * Settings/TYPO3.Flow/persistence.schema.yaml
 *
 * @Flow\Scope("singleton")
 */
class ConfigurationSchemaValidator {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\SchemaValidator
	 */
	protected $schemaValidator;

	/**
	 * Validate the given $configurationType and $path
	 *
	 * @param string $configurationType (optional) the configuration type to validate. if NULL, validates all configuration.
	 * @param string $path (optional) configuration path to validate
	 * @param array $loadedSchemaFiles (optional). if given, will be filled with a list of loaded schema files
	 * @return \TYPO3\Flow\Error\Result the result of the validation
	 * @throws Exception\SchemaValidationException
	 */
	public function validate($configurationType = NULL, $path = NULL, &$loadedSchemaFiles = array()) {
		if ($configurationType === NULL) {
			$configurationTypes = $this->configurationManager->getAvailableConfigurationTypes();
		} else {
			$configurationTypes = array($configurationType);
		}

		$result = new Result();
		foreach ($configurationTypes as $configurationType) {
			$resultForEachType = $this->validateSingleType($configurationType, $path, $loadedSchemaFiles);
			$result->forProperty($configurationType)->merge($resultForEachType);
		}
		return $result;
	}

	/**
	 * Validate a single configuration type
	 *
	 * @param string $configurationType the configuration typr to validate
	 * @param string $path configuration path to validate, or NULL.
	 * @param array $loadedSchemaFiles will be filled with a list of loaded schema files
	 * @return \TYPO3\Flow\Error\Result
	 * @throws Exception\SchemaValidationException
	 */
	protected function validateSingleType($configurationType, $path, &$loadedSchemaFiles) {
		$availableConfigurationTypes = $this->configurationManager->getAvailableConfigurationTypes();
		if (in_array($configurationType, $availableConfigurationTypes) === FALSE) {
			throw new Exception\SchemaValidationException('The configuration type "' . $configurationType . '" was not found. Only the following configuration types are supported: "' . implode('", "', $availableConfigurationTypes) . '"', 1364984886);
		}

		$configuration = $this->configurationManager->getConfiguration($configurationType);

			// find schema files for the given type and path
		$schemaFileInfos = array();
		$activePackages = $this->packageManager->getActivePackages();
		foreach ($activePackages as $package) {
			$packageKey = $package->getPackageKey();
			$packageSchemaPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($package->getResourcesPath(), 'Private/Schema'));
			if (is_dir($packageSchemaPath)) {
				$packageSchemaFiles = \TYPO3\Flow\Utility\Files::readDirectoryRecursively($packageSchemaPath, '.schema.yaml');
				foreach ($packageSchemaFiles as $schemaFile) {
					$schemaName = substr($schemaFile, strlen($packageSchemaPath) + 1, -strlen('.schema.yaml'));
					$schemaNameParts = explode('.', str_replace('/', '.', $schemaName), 2);

					$schemaType = $schemaNameParts[0];
					$schemaPath = isset($schemaNameParts[1]) ? $schemaNameParts[1] : NULL;

					if ($schemaType === $configurationType && ($path === NULL || strpos($schemaPath, $path) === 0)) {
						$schemaFileInfos[] = array(
							'file' => $schemaFile,
							'name' => $schemaName,
							'path' => $schemaPath,
							'packageKey' => $packageKey
						);
					}
				}
			}
		}

		if (count($schemaFileInfos) === 0) {
			throw new Exception\SchemaValidationException('No schema files found for configuration type "' . $configurationType . '"' . ($path !== NULL ? ' and path "' . $path . '".': '.'), 1364985056);
		}

		$result = new Result();
		foreach ($schemaFileInfos as $schemaFileInfo) {
			$loadedSchemaFiles[] = $schemaFileInfo['file'];

			if ($schemaFileInfo['path'] !== NULL) {
				$data = \TYPO3\Flow\Utility\Arrays::getValueByPath($configuration, $schemaFileInfo['path']);
			} else {
				$data = $configuration;
			}

			if (empty($data)) {
				throw new Exception\SchemaValidationException('The schema file "' . $schemaFileInfo['file'] . '" is empty.', 1364985445);
			} else {
				$parsedSchema = \Symfony\Component\Yaml\Yaml::parse($schemaFileInfo['file']);
				$validationResultForSingleSchema = $this->schemaValidator->validate($data, $parsedSchema);

				if ($schemaFileInfo['path'] !== NULL) {
					$result->forProperty($schemaFileInfo['path'])->merge($validationResultForSingleSchema);
				} else {
					$result->merge($validationResultForSingleSchema);
				}
			}
		}

		return $result;
	}
}
?>