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
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @Flow\Inject(lazy = FALSE)
	 * @var \TYPO3\Flow\Configuration\ConfigurationSchemaValidator
	 */
	protected $configurationSchemaValidator;

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
	 * List registered configuration types
	 *
	 * @return void
	 */
	public function listTypesCommand() {
		$this->outputLine('The following configuration types are registered:');
		$this->outputLine();

		foreach ($this->configurationManager->getAvailableConfigurationTypes() as $type) {
			$this->outputFormatted('- %s', array($type));
		}
	}

	/**
	 * Validate the given configuration
	 *
	 * <b>Validate all configuration</b>
	 * ./flow configuration:validate
	 *
	 * <b>Validate configuration at a certain subtype</b>
	 * ./flow configuration:validate --type Settings --path TYPO3.Flow.persistence
	 *
	 * You can retrieve the available configuration types with:
	 * ./flow configuration:listtypes
	 *
	 * @param string $type Configuration type to validate
	 * @param string $path path to the subconfiguration separated by "." like "TYPO3.Flow"
	 * @param boolean $verbose if TRUE, output more verbose information on the schema files which were used
	 * @return void
	 */
	public function validateCommand($type = NULL, $path = NULL, $verbose = FALSE) {
		if ($type === NULL) {
			$this->outputLine('Validating <b>all</b> configuration');
		} else {
			$this->outputLine('Validating <b>' . $type . '</b> configuration' . ($path !== NULL ? ' on path <b>' . $path . '</b>' : ''));
		}
		$this->outputLine();

		try {
			$validatedSchemaFiles = array();
			$result = $this->configurationSchemaValidator->validate($type, $path, $validatedSchemaFiles);
		} catch (\TYPO3\Flow\Configuration\Exception\SchemaValidationException $exception) {
			$this->outputLine('<b>Error:</b>');
			$this->outputFormatted($exception->getMessage(), array(), 4);
			$this->quit(2);
		}

		if ($verbose) {
			$this->outputLine('Loaded Schema Files:');
			foreach ($validatedSchemaFiles as $validatedSchemaFile) {
				$this->outputLine('- ' . substr($validatedSchemaFile, strlen(FLOW_PATH_ROOT)));
			}
			$this->outputLine();
		}

		if ($result->hasErrors()) {
			$errors = $result->getFlattenedErrors();
			$this->outputLine('<b>%s errors were found:</b>', array(count($errors)));
			foreach ($errors as $path => $pathErrors) {
				foreach ($pathErrors as $error) {
					$this->outputLine(' - %s -> %s', array($path, $error->render()));
				}
			}
			$this->quit(1);
		} else {
			$this->outputLine('<b>All Valid!</b>');
			$this->quit(0);
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
			if ($path !== NULL) {
				$data = \TYPO3\Flow\Utility\Arrays::getValueByPath($data, $path);
			}
		}

		if (empty($data)) {
			$this->outputLine('Data was not found or is empty');
			return;
		}

		$yaml = \Symfony\Component\Yaml\Yaml::dump($this->schemaGenerator->generate($data), 99);
		$this->output($yaml . chr(10));
	}

}
?>