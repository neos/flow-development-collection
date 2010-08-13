<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\View;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A JSON view
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class JsonView extends \F3\FLOW3\MVC\View\AbstractView {

	/**
	 * @var \F3\FLOW3\MVC\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * Only variables with a key contained in this array will be rendered
	 * @var array
	 */
	protected $variablesToRender = array('value');

	/**
	 * Sets the current controller context
	 *
	 * @param \F3\FLOW3\MVC\Controller\ControllerContext $controllerContext
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function setControllerContext(\F3\FLOW3\MVC\Controller\ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Specifies which variables this JsonView should render
	 * By default only variables with a name 'value' will be rendered
	 *
	 * @param array $variablesToRender
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function setVariablesToRender(array $variablesToRender) {
		$this->variablesToRender = $variablesToRender;
	}

	/**
	 * Transforms the value view variable to a serializable
	 * array represantion using a YAML view configuration and JSON encodes
	 * the result.
	 *
	 * @return string The JSON encoded variables
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	public function render() {
		$this->controllerContext->getResponse()->setHeader('Content-Type', 'application/json');
		$propertiesToRender = $this->renderArray();
		return json_encode($propertiesToRender);
	}

	/**
	 * Loads the configuration and transforms the value to a serializable
	 * array.
	 *
	 * @return array An array containing the values, ready to be JSON encoded
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @api
	 */
	protected function renderArray() {
		if (count($this->variablesToRender) === 1) {
			$variableName = current($this->variablesToRender);
			$valueToRender = isset($this->variables[$variableName]) ? $this->variables[$variableName] : NULL;
		} else {
			$valueToRender = array();
			foreach($this->variablesToRender as $variableName) {
				$valueToRender[$variableName] = isset($this->variables[$variableName]) ? $this->variables[$variableName] : NULL;
			}
		}
		$configuration = $this->loadConfigurationFromYamlFile();
		return $this->transformValue($valueToRender, $configuration);
	}

	/**
	 * Transforms a value depending on type recursively using the
	 * supplied configuration.
	 *
	 * @param mixed $value The value to transform
	 * @param mixed $configuration Configuration for transforming the value or NULL
	 * @return array The transformed value
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function transformValue($value, $configuration) {
		if (is_object($value)) {
			return $this->transformObject($value, $configuration);
		} elseif (is_array($value)) {
			$array = array();
			foreach ($value as $key => $element) {
				$array[$key] = $this->transformValue($element, $configuration);
			}
			return $array;
		} else {
			return $value;
		}
	}

	/**
	 * Traverses the given object structure in order to transform it into an
	 * array structure.
	 *
	 * @param object $object Object to traverse
	 * @param mixed $configuration Configuration for transforming the given object or NULL
	 * @return array Object structure as an aray
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function transformObject($object, $configuration) {
		$properties = \F3\FLOW3\Reflection\ObjectAccess::getGettableProperties($object);
		$propertiesToRender = array();
		foreach ($properties as $propertyName => $propertyValue) {
			if (isset($configuration['only']) && is_array($configuration['only']) && !in_array($propertyName, $configuration['only'])) continue;
			if (isset($configuration['exclude']) && is_array($configuration['exclude']) && in_array($propertyName, $configuration['exclude'])) continue;
			
			if (!is_array($propertyValue) && !is_object($propertyValue)) {
				$propertiesToRender[$propertyName] = $propertyValue;
			} elseif (isset($configuration['include']) && array_key_exists($propertyName, $configuration['include'])) {
				$propertiesToRender[$propertyName] = $this->transformObject($propertyValue, $configuration['include'][$propertyName]);
			}
		}
		return $propertiesToRender;
	}

	/**
	 * Loads and returns the configuration of this view, defined in a YAML file
	 * residing in the standard template path for the current controller and action.
	 * The file extension must be ".json.yaml"
	 *
	 * @return array The configuration, if any
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function loadConfigurationFromYamlFile() {
		$request = $this->controllerContext->getRequest();
		$configurationFilePath = 'resource://' . $request->getControllerPackageKey() . '/Private/Templates/' . str_replace('\\', '/', $request->getControllerName()) . '/' . ucfirst($request->getControllerActionName()) . '.json.yaml';
		if (file_exists($configurationFilePath)) {
			return \F3\FLOW3\Configuration\Source\YamlParser::loadFile($configurationFilePath);
		}
		return array();
	}
}

?>