<?php
declare(ENCODING = 'utf-8');
namespace F3\Kickstart\Controller;

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
 * Controller for the Kickstart generator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class KickstartController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var \F3\FLOW3\Package\ManagerInterface
	 * @inject
	 */
	protected $packageManager;

	/**
	 * @var \F3\Kickstart\Service\GeneratorService
	 * @inject
	 */
	protected $generatorService;

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array('F3\FLOW3\MVC\CLI\Request');

	/**
	 * Index action - displays a help message.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function indexAction() {
		$this->helpAction();
	}

	/**
	 * Help action - displays a help message
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function helpAction() {
		$this->response->appendContent(
			'FLOW3 Kickstart Generator' . PHP_EOL .
			'Usage:' . PHP_EOL .
			' php Public/index.php kickstart generator generatePackage --package-key <package-key>' . PHP_EOL .
			' php Public/index.php kickstart generator generateController --package-key <package-key> [--controller-name <controller-name>]' . PHP_EOL .
			' php Public/index.php kickstart generator generateModel --package-key <package-key> [--model-name <model-name>]' . PHP_EOL .
			' php Public/index.php kickstart generator generateRepository --package-key <package-key> [--model-name <model-name>]' . PHP_EOL .  PHP_EOL
		);
	}

	/**
	 * Kickstart a package
	 *
	 * @param string $packageKey The package key
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function generatePackageAction($packageKey) {
		if ($this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" already exists.' . PHP_EOL;
		}
		$this->packageManager->createPackage($packageKey);
		$this->packageManager->activatePackage($packageKey);
		return $this->generateControllerAction($packageKey);
	}

	/**
	 * Generate a controller for a package. The package key can contain
	 * a subpackage with a slash after the package key (e.g. "MyPackage/Admin").
	 *
	 * @param string $packageKey The package key of the package for the new controller with an optional subpackage
	 * @param string $controllerName The name for the new controller. This may also be a comma separated list of controller names.
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function generateControllerAction($packageKey, $controllerName = 'Standard') {
		$subpackageName = '';
		if (strpos('/', $packageKey) !== FALSE) {
			list($packageKey, $subpackageName) = explode('/', $packageKey, 2);
		}
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" is not available.' . PHP_EOL;
		}
		$generatedFiles = array();
		$controllerNames = \F3\FLOW3\Utility\Arrays::trimExplode(',', $controllerName);
		foreach ($controllerNames as $currentControllerName) {
			$generatedFiles += $this->generatorService->generateController($packageKey, $subpackageName, $currentControllerName);
		}
		return implode(PHP_EOL, $generatedFiles) . PHP_EOL;
	}

	/**
	 * Generate a model class for a package with a given set of fields.
	 * The fields are specified as a variable list of arguments with
	 * field name and type separated by a colon (e.g. "title:string size:int type:MyType").
	 *
	 * @param string $packageKey The package key of the package for the domain model
	 * @param string $modelName The name of the new domain model class
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function generateModelAction($packageKey, $modelName) {
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" is not available.' . PHP_EOL;
		}
		$fieldsArguments = $this->request->getCommandLineArguments();

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
	 * Generate a repository for a model given a package key and model name
	 *
	 * @param string $packageKey The package key
	 * @param string $modelName The name of the domain model class
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function generateRepositoryAction($packageKey, $modelName) {
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" is not available.' . PHP_EOL;
		}
		$generatedFiles = $this->generatorService->generateRepository($packageKey, $modelName);
		return implode(PHP_EOL, $generatedFiles) . PHP_EOL;
	}
}
?>