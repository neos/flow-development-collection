<?php
namespace TYPO3\Flow\Command;

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

/**
 * Configuration command controller for the TYPO3.Flow package
 *
 * @Flow\Scope("singleton")
 */
class ConfigurationCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\SchemaValidator
	 */
	protected $schemaValidator;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\SchemaGenerator
	 */
	protected $schemaGenerator;

	/**
	 * Show the active configuration settings
	 *
	 * The command shows the configuration of the current context as it is used by Flow itself.
	 * You can specify the configuration type and path if you want to show parts of the configuration.
	 *
	 * ./flow configuration:show --type Settings --path TYPO3.Flow.persistence
	 *
	 * @param string $type Configuration type to show
	 * @param string $path path to subconfiguration separated by "." like "TYPO3.Flow"
	 * @return void
	 */
	public function showCommand($type = NULL, $path = NULL) {
		$availableConfigurationTypes = $this->configurationManager->getAvailableConfigurationTypes();
		if (in_array($type, $availableConfigurationTypes)) {
			$configuration = $this->configurationManager->getConfiguration($type);
			if ($path !== NULL) {
				$configuration = \TYPO3\Flow\Utility\Arrays::getValueByPath($configuration, $path);
			}
			$typeAndPath = $type . ($path ? ': ' . $path : '');
			if ($configuration === NULL) {
				$this->outputLine('<b>Configuration "%s" was empty!</b>', array($typeAndPath));
			} else {
				$yaml = \Symfony\Component\Yaml\Yaml::dump($configuration, 99);
				$this->outputLine('<b>Configuration "%s":</b>', array($typeAndPath));
				$this->outputLine();
				$this->outputLine($yaml . chr(10));
			}
		} else {
			if ($type !== NULL) {
				$this->outputLine('<b>Configuration type "%s" was not found!</b>', array($type));
			}
			$this->outputLine('<b>Available configuration types:</b>');
			foreach ($availableConfigurationTypes as $availableConfigurationType) {
				$this->outputLine('  ' . $availableConfigurationType);
			}
			$this->outputLine();
			$this->outputLine('Hint: <b>%s configuration:show --type <configurationType></b>', array($this->getFlowInvocationString()));
			$this->outputLine('      shows the configuration of the specified type.');
		}
	}

	/**
	 * Validate the given configuration
	 *
	 * ./flow configuration:validate --type Settings --path TYPO3.Flow.persistence
	 *
	 * The schemas are searched in the path "Resources/Private/Schema" of all
	 * active Packages. The schema-filenames must match the pattern
	 * __type__.__path__.schema.yaml. The type and/or the path can also be
	 * expressed as subdirectories of Resources/Private/Schema. So
	 * Settings/TYPO3/Flow.persistence.schema.yaml will match the same pathes
	 * like Settings.TYPO3.Flow.persistence.schema.yaml or
	 * Settings/TYPO3.Flow/persistence.schema.yaml
	 *
	 * @param string $type Configuration type to validate
	 * @param string $path path to the subconfiguration separated by "." like "TYPO3.Flow"
	 * @return void
	 */
	public function validateCommand($type = NULL, $path = NULL) {
		$availableConfigurationTypes = $this->configurationManager->getAvailableConfigurationTypes();

		if (in_array($type, $availableConfigurationTypes) === FALSE) {
			if ($type !== NULL) {
				$this->outputLine('<b>Configuration type "%s" was not found!</b>', array($type));
				$this->outputLine();
			}
			$this->outputLine('<b>Available configuration types:</b>');
			foreach ($availableConfigurationTypes as $availableConfigurationType) {
				$this->outputLine('  ' . $availableConfigurationType);
			}
			$this->outputLine();
			$this->outputLine('Hint: <b>%s configuration:validate --type <configurationType></b>', array($this->getFlowInvocationString()));
			$this->outputLine('      validates the configuration of the specified type.');
			return;
		}

		$configuration = $this->configurationManager->getConfiguration($type);

		$this->outputLine('<b>Validating configuration for type: "' . $type . '"' . (($path !== NULL) ? ' and path: "' . $path . '"': '') . '</b>');

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
					$schemaNameParts = explode('.', str_replace('/', '.' ,$schemaName), 2);

					$schemaType = $schemaNameParts[0];
					$schemaPath = isset($schemaNameParts[1]) ? $schemaNameParts[1] : NULL;

					if ($schemaType === $type && ($path === NULL || strpos($schemaPath, $path) === 0)){
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

		$this->outputLine();
		if (count($schemaFileInfos) > 0) {
			$this->outputLine('%s schema files were found:', array(count($schemaFileInfos)));
			$result = new \TYPO3\Flow\Error\Result();
			foreach ($schemaFileInfos as $schemaFileInfo) {

				if ($schemaFileInfo['path'] !== NULL) {
					$data = \TYPO3\Flow\Utility\Arrays::getValueByPath($configuration, $schemaFileInfo['path']);
				} else {
					$data = $configuration;
				}

				if (empty($data)){
					$result->forProperty($schemaFileInfo['path'])->addError(new \TYPO3\Flow\Error\Error('configuration in path ' . $schemaFileInfo['path'] . ' is empty'));
					$this->outputLine(' - package: "' . $schemaFileInfo['packageKey'] . '" schema: "' . $schemaFileInfo['name'] . '" -> <b>configuration is empty</b>');
				} else {
					$parsedSchema = \Symfony\Component\Yaml\Yaml::parse($schemaFileInfo['file']);
					$schemaResult = $this->schemaValidator->validate($data, $parsedSchema);

					if ($schemaResult->hasErrors()) {
						$this->outputLine(' - package:"' . $schemaFileInfo['packageKey'] . '" schema:"' . $schemaFileInfo['name'] . '" -> <b>' .  count($schemaResult->getFlattenedErrors()) . ' errors</b>');
					} else {
						$this->outputLine(' - package:"' . $schemaFileInfo['packageKey'] . '" schema:"' . $schemaFileInfo['name'] . '" -> <b>is valid</b>');
					}

					if ($schemaFileInfo['path'] !== NULL) {
						$result->forProperty($schemaFileInfo['path'])->merge($schemaResult);
					} else {
						$result->merge($schemaResult);
					}
				}
			}
		} else {
			$this->outputLine('No matching schema-files were found!');
			return;
		}

		$this->outputLine();
		if ($result->hasErrors()) {
			$errors = $result->getFlattenedErrors();
			$this->outputLine('<b>%s errors were found:</b>', array(count($errors)));
			foreach ($errors as $path => $pathErrors){
				foreach ($pathErrors as $error){
					$this->outputLine(' - %s -> %s', array($path, $error->render()));
				}
			}
		} else {
			$this->outputLine('<b>The configuration is valid!</b>');
		}
	}

	/**
	 * Generate a schema for the given configuration or YAML file.
	 *
	 * ./flow configuration:generateschema --type Settings --path TYPO3.Flow.persistence
	 *
	 * The schema will be output to standard output.
	 *
	 * @param string $type Configuration type to create a schema for
	 * @param string $path path to the subconfiguration separated by "." like "TYPO3.Flow"
	 * @param string $yaml YAML file to create a schema for
	 * @return void
	 */
	public function generateSchemaCommand($type = NULL, $path = NULL, $yaml = NULL) {
		$data = NULL;
		if ($yaml !== NULL && is_file($yaml) && is_readable($yaml)) {
			$data = \Symfony\Component\Yaml\Yaml::parse($yaml);
		} elseif ($type !== NULL) {
			$data = $this->configurationManager->getConfiguration($type);
			if ($path !== NULL){
				$data = \TYPO3\Flow\Utility\Arrays::getValueByPath($data, $path);
			}
		}

		if (empty($data)){
			$this->outputLine('Data was not found or is empty');
			return;
		}

		$yaml = \Symfony\Component\Yaml\Yaml::dump($this->schemaGenerator->generate($data), 99);
		$this->output($yaml . chr(10));
	}

}
?>