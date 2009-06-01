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
 * @package Kickstart
 * @subpackage Service
 * @version $Id: DocBookManualRenderService.php 2465 2009-05-29 11:40:02Z k-fish $
 */

/**
 * Service for the Kickstart generator
 *
 * @package Kickstart
 * @subpackage Service
 * @version $Id: DocBookManualRenderService.php 2465 2009-05-29 11:40:02Z k-fish $
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class GeneratorService {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 * @inject
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Package\ManagerInterface
	 * @inject
	 */
	protected $packageManager;

	/**
	 * @var \F3\Fluid\Core\Parser\TemplateParser
	 * @inject
	 */
	protected $templateParser;

	/**
	 * @var array
	 */
	protected $generatedFiles = array();

	/**
	 * Generate a controller with the given name for the given package
	 *
	 * @param $packageKey
	 * @param $controllerName
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function generateController($packageKey, $controllerName) {
		$controllerClassName = ucfirst($controllerName) . 'Controller';

		$resourcesPath = $this->packageManager->getPackage('Kickstart')->getResourcesPath();

		$templatePathAndFilename = $resourcesPath . 'Private/Generator/Controller/ControllerTemplate.php.tmpl';

		$contextVariables['controllerClassName'] = $controllerClassName;
		$contextVariables['controllerName'] = $controllerName;
		$contextVariables['packageKey'] = $packageKey;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$controllerFilename = $controllerClassName . '.php';

		$controllerPath = $this->packageManager->getPackage($packageKey)->getClassesPath() . 'Controller/';

		$targetPathAndFilename = $controllerPath . $controllerFilename;

		$this->generateFile($targetPathAndFilename, $fileContent);

		$this->generateView($packageKey, $controllerName, 'index');

		return $this->generatedFiles;
	}

	/**
	 * Generate a view with the given name for the given package and controller
	 *
	 * @param $packageKey
	 * @param $controllerName
	 * @param $viewName
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function generateView($packageKey, $controllerName, $viewName) {
		$viewName = lcfirst($viewName);

		$resourcesPath = $this->packageManager->getPackage('Kickstart')->getResourcesPath();

		$templatePathAndFilename = $resourcesPath . 'Private/Generator/View/viewTemplate.html.tmpl';

		$contextVariables['controllerName'] = $controllerName;
		$contextVariables['packageKey'] = $packageKey;
		$contextVariables['viewName'] = $viewName;

		$fileContent = $this->renderTemplate($templatePathAndFilename, $contextVariables);

		$viewFilename = $viewName . '.html';

		$viewPath = $this->packageManager->getPackage($packageKey)->getResourcesPath() . 'Private/Templates/' . $controllerName . '/';

		$targetPathAndFilename = $viewPath . $viewFilename;

		$this->generateFile($targetPathAndFilename, $fileContent);

		return $this->generatedFiles;
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

		$flow3BasePath = realpath(FLOW3_PATH_PUBLIC . '../');
		$relativeTargetPathAndFilename = substr($targetPathAndFilename, strlen($flow3BasePath) + 1);

		$this->generatedFiles[] = '+ ' . $relativeTargetPathAndFilename;
	}

	/**
	 * Render the given template file with the given variables
	 *
	 * @param string $templatePathAndFilename
	 * @param array $contextVariables
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function renderTemplate($templatePathAndFilename, $contextVariables) {
		$templateSource = \F3\FLOW3\Utility\Files::getFileContents($templatePathAndFilename, FILE_TEXT);
		if ($templateSource === FALSE) {
			throw new \F3\Fluid\Core\RuntimeException('The template file "' . $templatePathAndFilename . '" could not be loaded.', 1225709595);
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
	protected function buildRenderingContext($contextVariables) {
		$variableContainer = $this->objectFactory->create('F3\Fluid\Core\ViewHelper\TemplateVariableContainer', $contextVariables);
		$renderingConfiguration = $this->objectFactory->create('F3\Fluid\Core\Rendering\RenderingConfiguration');
		$renderingConfiguration->setObjectAccessorPostProcessor($this->objectFactory->create('F3\Fluid\Core\Rendering\HTMLSpecialCharsPostProcessor'));

		$renderingContext = $this->objectFactory->create('F3\Fluid\Core\Rendering\RenderingContext');
		$renderingContext->setTemplateVariableContainer($variableContainer);
		$renderingContext->setRenderingConfiguration($renderingConfiguration);

		$viewHelperVariableContainer = $this->objectFactory->create('F3\Fluid\Core\ViewHelper\ViewHelperVariableContainer');
		$renderingContext->setViewHelperVariableContainer($viewHelperVariableContainer);

		return $renderingContext;
	}
}
?>
