<?php
namespace TYPO3\Kickstart\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Kickstart".       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Kickstart\Utility\Validation;

/**
 * Command controller for the Kickstart generator
 *
 */
class KickstartCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Kickstart\Service\GeneratorService
	 */
	protected $generatorService;

	/**
	 * Kickstart a new package
	 *
	 * Creates a new package and creates a standard Action Controller and a sample
	 * template for its Index Action.
	 *
	 * For creating a new package without sample code use the package:create command.
	 *
	 * @param string $packageKey The package key, for example "MyCompany.MyPackageName"
	 * @return string
	 * @see typo3.flow:package:create
	 */
	public function packageCommand($packageKey) {
		$this->validatePackageKey($packageKey);

		if ($this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('Package "%s" already exists.', array($packageKey));
			exit(2);
		}
		$this->packageManager->createPackage($packageKey);
		$this->actionControllerCommand($packageKey, 'Standard');
	}

	/**
	 * Kickstart a new action controller
	 *
	 * Generates an Action Controller with the given name in the specified package.
	 * In its default mode it will create just the controller containing a sample
	 * indexAction.
	 *
	 * By specifying the --generate-actions flag, this command will also create a
	 * set of actions. If no model or repository exists which matches the
	 * controller name (for example "CoffeeRepository" for "CoffeeController"),
	 * an error will be shown.
	 *
	 * Likewise the command exits with an error if the specified package does not
	 * exist. By using the --generate-related flag, a missing package, model or
	 * repository can be created alongside, avoiding such an error.
	 *
	 * By specifying the --generate-templates flag, this command will also create
	 * matching Fluid templates for the actions created. This option can only be
	 * used in combination with --generate-actions.
	 *
	 * The default behavior is to not overwrite any existing code. This can be
	 * overridden by specifying the --force flag.
	 *
	 * @param string $packageKey The package key of the package for the new controller with an optional subpackage, (e.g. "MyCompany.MyPackage/Admin").
	 * @param string $controllerName The name for the new controller. This may also be a comma separated list of controller names.
	 * @param boolean $generateActions Also generate index, show, new, create, edit, update and delete actions.
	 * @param boolean $generateTemplates Also generate the templates for each action.
	 * @param boolean $generateRelated Also create the mentioned package, related model and repository if neccessary.
	 * @param boolean $force Overwrite any existing controller or template code. Regardless of this flag, the package, model and repository will never be overwritten.
	 * @return string
	 * @see typo3.kickstart:kickstart:commandcontroller
	 */
	public function actionControllerCommand($packageKey, $controllerName, $generateActions = FALSE, $generateTemplates = TRUE, $generateRelated = FALSE, $force = FALSE) {
		$subpackageName = '';
		if (strpos($packageKey, '/') !== FALSE) {
			list($packageKey, $subpackageName) = explode('/', $packageKey, 2);
		}
		$this->validatePackageKey($packageKey);
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			if ($generateRelated === FALSE) {
				$this->outputLine('Package "%s" is not available.', array($packageKey));
				$this->outputLine('Hint: Use --generate-related for creating it!');
				exit(2);
			}
			$this->packageManager->createPackage($packageKey);
		}
		$generatedFiles = array();
		$generatedModels = FALSE;

		$controllerNames = \TYPO3\Flow\Utility\Arrays::trimExplode(',', $controllerName);
		if ($generateActions === TRUE) {
			foreach ($controllerNames as $currentControllerName) {
				$modelClassName = str_replace('.', '\\', $packageKey) . '\Domain\Model\\' . $currentControllerName;
				if (!class_exists($modelClassName)) {
					if ($generateRelated === TRUE) {
						$generatedFiles += $this->generatorService->generateModel($packageKey, $currentControllerName, array('name' => array('type' => 'string')));
						$generatedModels = TRUE;
					} else {
						$this->outputLine('The model %s does not exist, but is necessary for creating the respective actions.', array($modelClassName));
						$this->outputLine('Hint: Use --generate-related for creating it!');
						exit(3);
					}
				}

				$repositoryClassName = str_replace('.', '\\', $packageKey) . '\Domain\Repository\\' . $currentControllerName . 'Repository';
				if (!class_exists($repositoryClassName)) {
					if ($generateRelated === TRUE) {
						$generatedFiles += $this->generatorService->generateRepository($packageKey, $currentControllerName);
					} else {
						$this->outputLine('The repository %s does not exist, but is necessary for creating the respective actions.', array($repositoryClassName));
						$this->outputLine('Hint: Use --generate-related for creating it!');
						exit(4);
					}
				}
			}
		}

		foreach ($controllerNames as $currentControllerName) {
			if ($generateActions === TRUE) {
				$generatedFiles += $this->generatorService->generateCrudController($packageKey, $subpackageName, $currentControllerName, $force);
			} else {
				$generatedFiles += $this->generatorService->generateActionController($packageKey, $subpackageName, $currentControllerName, $force);
			}
			if ($generateTemplates === TRUE) {
				$generatedFiles += $this->generatorService->generateLayout($packageKey, 'Default', $force);
				if ($generateActions === TRUE) {
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Index', 'Index', $force);
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'New', 'New', $force);
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Edit', 'Edit', $force);
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Show', 'Show', $force);
				} else {
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Index', 'SampleIndex', $force);
				}
			}
		}

		$this->outputLine(implode(PHP_EOL, $generatedFiles));

		if ($generatedModels === TRUE) {
			$this->outputLine('As new models were generated, don\'t forget to update the database schema with the respective doctrine:* commands.');
		}
	}

	/**
	 * Kickstart a new command controller
	 *
	 * Creates a new command controller with the given name in the specified
	 * package. The generated controller class already contains an example command.
	 *
	 * @param string $packageKey The package key of the package for the new controller
	 * @param string $controllerName The name for the new controller. This may also be a comma separated list of controller names.
	 * @param boolean $force Overwrite any existing controller.
	 * @return string
	 * @see typo3.kickstart:kickstart:actioncontroller
	 */
	public function commandControllerCommand($packageKey, $controllerName, $force = FALSE) {
		$this->validatePackageKey($packageKey);
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('Package "%s" is not available.', array($packageKey));
			exit(2);
		}
		$generatedFiles = array();
		$controllerNames = \TYPO3\Flow\Utility\Arrays::trimExplode(',', $controllerName);
		foreach ($controllerNames as $currentControllerName) {
			$generatedFiles += $this->generatorService->generateCommandController($packageKey, $currentControllerName, $force);
		}
		$this->outputLine(implode(PHP_EOL, $generatedFiles));
	}

	/**
	 * Kickstart a new domain model
	 *
	 * This command generates a new domain model class. The fields are specified as
	 * a variable list of arguments with field name and type separated by a colon
	 * (for example "title:string" "size:int" "type:MyType").
	 *
	 * @param string $packageKey The package key of the package for the domain model
	 * @param string $modelName The name of the new domain model class
	 * @param boolean $force Overwrite any existing model.
	 * @return string
	 * @see typo3.kickstart:kickstart:repository
	 */
	public function modelCommand($packageKey, $modelName, $force = FALSE) {
		$this->validatePackageKey($packageKey);
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('Package "%s" is not available.', array($packageKey));
			exit(2);
		}

		$this->validateModelName($modelName);

		$fieldsArguments = $this->request->getExceedingArguments();
		$fieldDefinitions = array();
		foreach ($fieldsArguments as $fieldArgument) {
			list($fieldName, $fieldType) = explode(':', $fieldArgument, 2);

			$fieldDefinitions[$fieldName] = array('type' => $fieldType);
			if (strpos($fieldType, 'array') !== FALSE) {
				$fieldDefinitions[$fieldName]['typeHint'] = 'array';
			} elseif (strpos($fieldType, '\\') !== FALSE) {
				if (strpos($fieldType, '<') !== FALSE) {
					$fieldDefinitions[$fieldName]['typeHint'] = substr($fieldType, 0, strpos($fieldType, '<'));
				} else {
					$fieldDefinitions[$fieldName]['typeHint'] = $fieldType;
				}
			}
		};

		$generatedFiles = $this->generatorService->generateModel($packageKey, $modelName, $fieldDefinitions, $force);
		$this->outputLine(implode(PHP_EOL, $generatedFiles));
		$this->outputLine('As a new model was generated, don\'t forget to update the database schema with the respective doctrine:* commands.');
	}

	/**
	 * Kickstart a new domain repository
	 *
	 * This command generates a new domain repository class for the given model name.
	 *
	 * @param string $packageKey The package key
	 * @param string $modelName The name of the domain model class
	 * @param boolean $force Overwrite any existing repository.
	 * @return string
	 * @see typo3.kickstart:kickstart:model
	 */
	public function repositoryCommand($packageKey, $modelName, $force = FALSE) {
		$this->validatePackageKey($packageKey);
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			$this->outputLine('Package "%s" is not available.', array($packageKey));
			exit(2);
		}

		$generatedFiles = $this->generatorService->generateRepository($packageKey, $modelName, $force);
		$this->outputLine(implode(PHP_EOL, $generatedFiles));
	}

	/**
	 * Checks the syntax of the given $packageKey and quits with an error message if it's not valid
	 *
	 * @param string $packageKey
	 * @return void
	 */
	protected function validatePackageKey($packageKey) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			$this->outputLine('Package key "%s" is not valid. Only UpperCamelCase with alphanumeric characters in the format <VendorName>.<PackageKey>, please!', array($packageKey));
			exit(1);
		}
	}

	/**
	 * Check the given model name to be not one of the reserved words of PHP.
	 *
	 * @param string $modelName
	 * @return boolean
	 * @see http://www.php.net/manual/en/reserved.keywords.php
	 */
	protected function validateModelName($modelName) {
		if (Validation::isReservedKeyword($modelName)) {
			$this->outputLine('The name of the model cannot be one of the reserved words of PHP!');
			$this->outputLine('Have a look at: http://www.php.net/manual/en/reserved.keywords.php');
			exit(3);
		}
	}
}
