<?php
namespace TYPO3\Kickstart\Command;

/*                                                                        *
 * This script belongs to the FLOW3 package "Kickstart".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Command controller for the Kickstart generator
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class KickstartCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 * @inject
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\Kickstart\Service\GeneratorService
	 * @inject
	 */
	protected $generatorService;

	/**
	 * Kickstart a new package
	 *
	 * Creates a new package and creates a standard Action Controller and a sample template for its Index Action.
	 * For creating a new package without sample code use the package:create command.
	 *
	 * @param string $packageKey The package key, for example "MyCompany.MyPackageName"
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function packageCommand($packageKey) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			return 'Package key "' . $packageKey . '" is not valid. Only UpperCamelCase with alphanumeric characters and underscore, please!' . PHP_EOL;
		}

		if ($this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" already exists.' . PHP_EOL;
		}
		$this->packageManager->createPackage($packageKey);
		return $this->actionControllerCommand($packageKey, 'Standard');
	}

	/**
	 * Kickstart a new action controller
	 *
	 * Generates an Action Controller with the given name in the specified package. In its default mode it will create
	 * just the controller containing a sample indexAction.
	 *
	 * By specifying the --generate-actions flag, this command will also create a set of actions. If no model or repository
	 * exists which matches the controller name (for example "CoffeeRepository" for "CoffeeController"), an error will be
	 * shown. Likewise the command exists with an error of the specified package does not exist. By using the --generate-related
	 * flag, missing package, model or repository can be created alongside, avoiding such an error.
	 *
	 * By specifying the --generate-templates flag, this command will also create matching Fluid templates for the actions
	 * created. This option can only be used in combination with --generate-actions.
	 *
	 * The default behavior is to not overwrite any existing code. This can be overridden by specifying the --force flag.
	 *
	 * @param string $packageKey The package key of the package for the new controller with an optional subpackage, (e.g. "MyCompany.MyPackage/Admin").
	 * @param string $controllerName The name for the new controller. This may also be a comma separated list of controller names.
	 * @param boolean $generateActions Also generate index, new, create, edit, update and delete actions.
	 * @param boolean $generateTemplates Also generate the templates for each action.
	 * @param boolean $generateRelated Also create the mentioned package, related model and repository if neccessary.
	 * @param boolean $force Overwrite any existing controller or template code. Regardless of this flag, the package, model and repository will never be overwritten.
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function actionControllerCommand($packageKey, $controllerName, $generateActions = FALSE, $generateTemplates = TRUE, $generateRelated = FALSE, $force = FALSE) {
		$subpackageName = '';
		if (strpos('/', $packageKey) !== FALSE) {
			list($packageKey, $subpackageName) = explode('/', $packageKey, 2);
		}
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			return 'Package key "' . $packageKey . '" is not valid. Only UpperCamelCase with alphanumeric characters and underscore, please!' . PHP_EOL;
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			if ($generateRelated === FALSE) {
				return 'Package "' . $packageKey . '" is not available.' . PHP_EOL . 'Hint: Use --generate-related for creating it!' . PHP_EOL;
			}
			$this->packageManager->createPackage($packageKey);
		}
		$generatedFiles = array();
		if ($generateActions === TRUE) {
			$modelClassName = str_replace('.', '\\', $packageKey) . '\Domain\Model\\' . $controllerName;
			if (!class_exists($modelClassName)) {
				if ($generateRelated === TRUE) {
					$generatedFiles += $this->generatorService->generateModel($packageKey, $controllerName, array('name' => array('type' => 'string')));
				} else {
					return sprintf('The model %s does not exist, but is necessary for creating the respective actions.', $modelClassName) . PHP_EOL . 'Hint: Use --generate-related for creating it!' . PHP_EOL;
				}
			}

			$repositoryClassName = str_replace('.', '\\', $packageKey) . '\Domain\Repository\\' . $controllerName . 'Repository';
			if (!class_exists($repositoryClassName)) {
				if ($generateRelated === TRUE) {
					$generatedFiles += $this->generatorService->generateRepository($packageKey, $controllerName);
				} else {
					return sprintf('The repository %s does not exist, but is necessary for creating the respective actions.', $repositoryClassName) . PHP_EOL . 'Hint: Use --generate-related for creating it!' . PHP_EOL;
				}
			}
		}

		$controllerNames = \TYPO3\FLOW3\Utility\Arrays::trimExplode(',', $controllerName);
		foreach ($controllerNames as $currentControllerName) {
			if ($generateActions === TRUE) {
				$generatedFiles += $this->generatorService->generateCrudController($packageKey, $subpackageName, $currentControllerName, $force);
			} else {
				$generatedFiles += $this->generatorService->generateActionController($packageKey, $subpackageName, $currentControllerName, $force);
			}
			if ($generateTemplates === TRUE) {
				if ($generateActions === TRUE) {
					$generatedFiles += $this->generatorService->generateLayout($packageKey, 'Default', $force);

					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Index', 'Index', $force);
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'New', 'New', $force);
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Edit', 'Edit', $force);
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Show', 'Show', $force);
				} else {
					$generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Index', 'SampleIndex', $force);
				}
			}
		}

		return implode(PHP_EOL, $generatedFiles) . PHP_EOL;
	}

	/**
	 * Kickstart a new command controller
	 *
	 * Creates a new command controller with the given name in the specified package. The generated controller class
	 * already contains an example command.
	 *
	 * @param string $packageKey The package key of the package for the new controller
	 * @param string $controllerName The name for the new controller. This may also be a comma separated list of controller names.
	 * @param boolean $force Overwrite any existing controller.
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function commandControllerCommand($packageKey, $controllerName, $force = FALSE) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			return 'Package key "' . $packageKey . '" is not valid. Only UpperCamelCase with alphanumeric characters and underscore, please!' . PHP_EOL;
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" is not available.' . PHP_EOL;
		}
		$generatedFiles = array();
		$controllerNames = \TYPO3\FLOW3\Utility\Arrays::trimExplode(',', $controllerName);
		foreach ($controllerNames as $currentControllerName) {
			$generatedFiles += $this->generatorService->generateCommandController($packageKey, $currentControllerName, $force);
		}
		return implode(PHP_EOL, $generatedFiles) . PHP_EOL;
	}

	/**
	 * Kickstart a new domain model
	 *
	 * The fields are specified as a variable list of arguments with field name and type separated by a colon (e.g.
	 * "title:string size:int type:MyType").
	 *
	 * @param string $packageKey The package key of the package for the domain model
	 * @param string $modelName The name of the new domain model class
	 * @param boolean $force Overwrite any existing model.
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function modelCommand($packageKey, $modelName, $force = FALSE) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			return 'Package key "' . $packageKey . '" is not valid. Only UpperCamelCase with alphanumeric characters and ".", please!' . PHP_EOL;
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" is not available.' . PHP_EOL;
		}

		$fieldsArguments = $this->request->getExceedingArguments();
		$fieldDefinitions = array();
		foreach ($fieldsArguments as $fieldArgument) {
			list($fieldName, $fieldType) = explode(':', $fieldArgument, 2);

			$fieldDefinitions[$fieldName] = array('type' => $fieldType);
			if (strpos($fieldType, 'array') !== FALSE) {
				$fieldDefinitions[$fieldName]['typeHint'] = 'array';
			} elseif (strpos($fieldType, '\\') !== FALSE) {
				$fieldDefinitions[$fieldName]['typeHint'] = $fieldType;
			}
		};

		$generatedFiles = $this->generatorService->generateModel($packageKey, $modelName, $fieldDefinitions, $force);
		return implode(PHP_EOL, $generatedFiles) . PHP_EOL;
	}

	/**
	 * Kickstart a new domain repository
	 *
	 * @param string $packageKey The package key
	 * @param string $modelName The name of the domain model class
	 * @param boolean $force Overwrite any existing repository.
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function repositoryCommand($packageKey, $modelName, $force = FALSE) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			return 'Package key "' . $packageKey . '" is not valid. Only UpperCamelCase with alphanumeric characters and underscore, please!' . PHP_EOL;
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" is not available.' . PHP_EOL;
		}

		$generatedFiles = $this->generatorService->generateRepository($packageKey, $modelName, $force);
		return implode(PHP_EOL, $generatedFiles) . PHP_EOL;
	}

}
?>