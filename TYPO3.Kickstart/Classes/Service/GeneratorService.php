<?php
declare(ENCODING = 'utf-8');
namespace F3\Kickstart\Service;

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
 * Service for the Kickstart generator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GeneratorService {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 * @inject
	 */
	protected $packageManager;

	/**
	 * @var \F3\Fluid\Core\Parser\TemplateParser
	 * @inject
	 */
	protected $templateParser;

	/**
	 * @var \F3\Kickstart\Utility\Inflector
	 * @inject
	 */
	protected $inflector;

	/**
	 * @var array
	 */
	protected $generatedFiles = array();

	/**
	 * Generate a controller with the given name for the given package
	 *
	 * @param string $packageKey The package key of the controller's package
	 * @param string $subpackage An optional subpackage name
	 * @param string $controllerName The name of the new controller
	 * @return array An array of generated filenames
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function generateController($packageKey, $subpackage, $controllerName) {
		$controllerClassName = ucfirst($controllerName) . 'Controller';

		$templatePathAndFilename = 'resource://Kickstart/Private/Generator/Controller/ControllerTemplate.php.tmpl';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['subpackage'] = $subpackage;
		$contextVariables['isInSubpackage'] = ($subpackage != '');
		$contextVariables['controllerClassName'] = $controllerClassName;
		$contextVariables['controllerName'] = $controllerName;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$subpackagePath = $subpackage != '' ? $subpackage . '/' : '';
		$controllerFilename = $controllerClassName . '.php';
		$controllerPath = $this->packageManager->getPackage($packageKey)->getClassesPath() . $subpackagePath . 'Controller/';
		$targetPathAndFilename = $controllerPath . $controllerFilename;

		$this->generateFile($targetPathAndFilename, $fileContent);

		$this->generateView($packageKey, $subpackage, $controllerName, 'Index');

		return $this->generatedFiles;
	}

	/**
	 * Generate a view with the given name for the given package and controller
	 *
	 * @param string $packageKey The package key of the controller's package
	 * @param string $subpackage An optional subpackage name
	 * @param string $controllerName The name of the new controller
	 * @param string $viewName The name of the view
	 * @return array An array of generated filenames
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function generateView($packageKey, $subpackage, $controllerName, $viewName) {
		$viewName = ucfirst($viewName);

		$templatePathAndFilename = 'resource://Kickstart/Private/Generator/View/ViewTemplate.html.tmpl';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['subpackage'] = $subpackage;
		$contextVariables['isInSubpackage'] = ($subpackage != '');
		$contextVariables['controllerName'] = $controllerName;
		$contextVariables['viewName'] = $viewName;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$subpackagePath = $subpackage != '' ? $subpackage . '/' : '';
		$viewFilename = $viewName . '.html';
		$viewPath = 'resource://' . $packageKey . '/Private/Templates/' . $subpackagePath . $controllerName . '/';
		$targetPathAndFilename = $viewPath . $viewFilename;

		$this->generateFile($targetPathAndFilename, $fileContent);

		return $this->generatedFiles;
	}

	/**
	 * Generate a model for the package with the given model name and fields
	 *
	 * @param string $packageKey The package key of the controller's package
	 * @param string $modelName The name of the new model
	 * @param array $fieldDefinitions The field definitions
	 * @return array An array of generated filenames
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function generateModel($packageKey, $modelName, array $fieldDefinitions) {
		$modelName = ucfirst($modelName);
		$namespace = 'F3\\' . $packageKey .  '\\Domain\\Model';
		$fieldDefinitions = $this->normalizeFieldDefinitions($fieldDefinitions, $namespace);

		$templatePathAndFilename = 'resource://Kickstart/Private/Generator/Model/EntityTemplate.php.tmpl';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['modelName'] = $modelName;
		$contextVariables['fieldDefinitions'] = $fieldDefinitions;
		$contextVariables['namespace'] = $namespace;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$modelFilename = $modelName . '.php';
		$modelPath = $this->packageManager->getPackage($packageKey)->getClassesPath() . 'Domain/Model/';
		$targetPathAndFilename = $modelPath . $modelFilename;

		$this->generateFile($targetPathAndFilename, $fileContent);

		return $this->generatedFiles;
	}

	/**
	 * Generate a repository for a model given a model name and package key
	 *
	 * @param string $packageKey The package key
	 * @param string $modelName The name of the model
	 * @return array An array of generated filenames
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function generateRepository($packageKey, $modelName) {
		$modelName = ucfirst($modelName);
		$repositoryClassName = $modelName . 'Repository';
		$namespace = 'F3\\' . $packageKey .  '\\Domain\\Repository';

		$templatePathAndFilename = 'resource://Kickstart/Private/Generator/Repository/RepositoryTemplate.php.tmpl';

		$contextVariables = array();
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['modelName'] = $modelName;
		$contextVariables['repositoryClassName'] = $repositoryClassName;
		$contextVariables['namespace'] = $namespace;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$repositoryFilename = $repositoryClassName . '.php';
		$repositoryPath = $this->packageManager->getPackage($packageKey)->getClassesPath() . 'Domain/Repository/';
		$targetPathAndFilename = $repositoryPath . $repositoryFilename;

		$this->generateFile($targetPathAndFilename, $fileContent);

		return $this->generatedFiles;
	}

	/**
	 * Normalize types and prefix types with namespaces
	 *
	 * @param array $fieldDefinitions The field definitions
	 * @param string $namespace The namespace
	 * @return array The normalized and type converted field definitions
	 */
	protected function normalizeFieldDefinitions(array $fieldDefinitions, $namespace = '') {
		foreach ($fieldDefinitions as &$fieldDefinition) {
			if ($fieldDefinition['type'] == 'bool') {
				$fieldDefinition['type'] = 'boolean';
			} elseif ($fieldDefinition['type'] == 'int') {
				$fieldDefinition['type'] = 'integer';
			} else if (preg_match('/^[A-Z]/', $fieldDefinition['type'])) {
				if (class_exists($fieldDefinition['type'])) {
					$fieldDefinition['type'] = '\\' . $fieldDefinition['type'];
				} else {
					$fieldDefinition['type'] = '\\' . $namespace . '\\' . $fieldDefinition['type'];
				}
			}
		}
		return $fieldDefinitions;
	}

	/**
	 * Generate a file with the given content and add it to the
	 * generated files
	 *
	 * @param string $targetPathAndFilename
	 * @param string $fileContent
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function generateFile($targetPathAndFilename, $fileContent) {
		if (!is_dir(dirname($targetPathAndFilename))) {
			\F3\FLOW3\Utility\Files::createDirectoryRecursively(dirname($targetPathAndFilename));
		}
		file_put_contents($targetPathAndFilename, $fileContent);
		$relativeTargetPathAndFilename = substr($targetPathAndFilename, strlen(FLOW3_PATH_ROOT) - 1);
		$this->generatedFiles[] = '+ ...' . $relativeTargetPathAndFilename;
	}

	/**
	 * Render the given template file with the given variables
	 *
	 * @param string $templatePathAndFilename
	 * @param array $contextVariables
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function renderTemplate($templatePathAndFilename, array $contextVariables) {
		$templateSource = \F3\FLOW3\Utility\Files::getFileContents($templatePathAndFilename, FILE_TEXT);
		if ($templateSource === FALSE) {
			throw new \F3\Fluid\Core\Exception('The template file "' . $templatePathAndFilename . '" could not be loaded.', 1225709595);
		}
		$parsedTemplate = $this->templateParser->parse($templateSource);

		$renderingContext = $this->buildRenderingContext($contextVariables);

		return $parsedTemplate->render($renderingContext);
	}

	/**
	 * Build the rendering context
	 *
	 * @param array $contextVariables
	 */
	protected function buildRenderingContext(array $contextVariables) {
		$renderingContext = $this->objectManager->create('F3\Fluid\Core\Rendering\RenderingContextInterface');

		$renderingContext->injectTemplateVariableContainer($this->objectManager->create('F3\Fluid\Core\ViewHelper\TemplateVariableContainer', $contextVariables));
		$renderingContext->injectViewHelperVariableContainer($this->objectManager->create('F3\Fluid\Core\ViewHelper\ViewHelperVariableContainer'));

		return $renderingContext;
	}
}
?>
