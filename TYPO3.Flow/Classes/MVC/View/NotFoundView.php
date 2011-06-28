<?php
namespace TYPO3\FLOW3\MVC\View;

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
 * The not found view - a special case.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class NotFoundView extends \TYPO3\FLOW3\MVC\View\AbstractView {

	/**
	 * @var \TYPO3\FLOW3\MVC\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var array
	 */
	protected $variablesMarker = array('errorMessage' => 'ERROR_MESSAGE');

	/**
	 * Renders the not found view
	 *
	 * @return string The rendered view
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @throws \TYPO3\FLOW3\MVC\Exception if no request has been set
	 * @api
	 */
	public function render() {
		if (!is_object($this->controllerContext->getRequest())) throw new \TYPO3\FLOW3\MVC\Exception('Can\'t render view without request object.', 1192450280);

		$template = file_get_contents($this->getTemplatePathAndFilename());

		if ($this->controllerContext->getRequest() instanceof \TYPO3\FLOW3\MVC\Web\Request) {
			$template = str_replace('###BASEURI###', $this->controllerContext->getRequest()->getBaseUri(), $template);
		}

		foreach ($this->variablesMarker as $variableName => $marker) {
			$variableValue = isset($this->variables[$variableName]) ? $this->variables[$variableName] : '';
			$template = str_replace('###' . $marker . '###', $variableValue, $template);
		}

		return $template;
	}

	/**
	 * Retrieves path and filename of the not-found-template
	 *
	 * @return string path and filename of the not-found-template
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getTemplatePathAndFilename() {
		return FLOW3_PATH_FLOW3 . 'Resources/Private/MVC/NotFoundView_Template.html';
	}

	/**
	 * A magic call method.
	 *
	 * Because this not found view is used as a Special Case in situations,
	 * it must be able to handle method calls which originally were
	 * directed to another type of view. This magic method should prevent PHP from issuing
	 * a fatal error.
	 *
	 * @param string $methodName Name of the method
	 * @param array $arguments Arguments passed to the method
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __call($methodName, array $arguments) {
	}
}

?>