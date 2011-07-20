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
	 * Kickstart a package
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
		return $this->actioncontrollerCommand($packageKey);
	}

	/**
	 * Kickstart an action controller
	 *
	 * The package key can contain a subpackage with a slash after the package key (e.g. "MyCompany.MyPackage/Admin").
	 *
	 * @param string $packageKey The package key of the package for the new controller with an optional subpackage
	 * @param string $controllerName The name for the new controller. This may also be a comma separated list of controller names.
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function actionControllerCommand($packageKey, $controllerName = 'Standard') {
		$subpackageName = '';
		if (strpos('/', $packageKey) !== FALSE) {
			list($packageKey, $subpackageName) = explode('/', $packageKey, 2);
		}
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			return 'Package key "' . $packageKey . '" is not valid. Only UpperCamelCase with alphanumeric characters and underscore, please!' . PHP_EOL;
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" is not available.' . PHP_EOL;
		}
		$generatedFiles = array();
		$controllerNames = \TYPO3\FLOW3\Utility\Arrays::trimExplode(',', $controllerName);
		foreach ($controllerNames as $currentControllerName) {
			$generatedFiles += $this->generatorService->generateController($packageKey, $subpackageName, $currentControllerName);
		}
		return implode(PHP_EOL, $generatedFiles) . PHP_EOL;
	}

	/**
	 * Kickstart a command controller
	 *
	 * Creates a new command controller with the given name in the specified package. The generated controller class
	 * already contains an example command.
	 *
	 * @param string $packageKey The package key of the package for the new controller
	 * @param string $controllerName The name for the new controller. This may also be a comma separated list of controller names.
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function commandControllerCommand($packageKey, $controllerName) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			return 'Package key "' . $packageKey . '" is not valid. Only UpperCamelCase with alphanumeric characters and underscore, please!' . PHP_EOL;
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" is not available.' . PHP_EOL;
		}
		$generatedFiles = array();
		$controllerNames = \TYPO3\FLOW3\Utility\Arrays::trimExplode(',', $controllerName);
		foreach ($controllerNames as $currentControllerName) {
			$generatedFiles += $this->generatorService->generateCommandController($packageKey, $currentControllerName);
		}
		return implode(PHP_EOL, $generatedFiles) . PHP_EOL;
	}

	/**
	 * Kickstart a domain model
	 *
	 * The fields are specified as a variable list of arguments with field name and type separated by a colon (e.g.
	 * "title:string size:int type:MyType").
	 *
	 * @param string $packageKey The package key of the package for the domain model
	 * @param string $modelName The name of the new domain model class
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function modelCommand($packageKey, $modelName) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			return 'Package key "' . $packageKey . '" is not valid. Only UpperCamelCase with alphanumeric characters and ".", please!' . PHP_EOL;
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" is not available.' . PHP_EOL;
		}

		$fieldsArguments = $this->request->getArguments();
		array_shift($fieldsArguments);
		array_shift($fieldsArguments);
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
		$generatedFiles = $this->generatorService->generateModel($packageKey, $modelName, $fieldDefinitions);
		return implode(PHP_EOL, $generatedFiles) . PHP_EOL;
	}

	/**
	 * Kickstart a domain repository
	 *
	 * @param string $packageKey The package key
	 * @param string $modelName The name of the domain model class
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function repositoryCommand($packageKey, $modelName) {
		if (!$this->packageManager->isPackageKeyValid($packageKey)) {
			return 'Package key "' . $packageKey . '" is not valid. Only UpperCamelCase with alphanumeric characters and underscore, please!' . PHP_EOL;
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" is not available.' . PHP_EOL;
		}
		$generatedFiles = $this->generatorService->generateRepository($packageKey, $modelName);
		return implode(PHP_EOL, $generatedFiles) . PHP_EOL;
	}

}
?>