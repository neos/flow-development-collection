<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web;

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
 * Builds a web sub request object
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class SubRequestBuilder {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager A reference to the object factory
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the server environment
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment The environment
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Builds a sub request object. If you define the SubRequestClassName,
	 * the class specified MUST be a subclass of F3\FLOW3\MVC\Web\SubRequest
	 *
	 * @param \F3\FLOW3\MVC\Web\Request $parentRequest
	 * @param string $argumentNamespace namespace that will be prefixed to URIs of this sub request
	 * @param string $subRequestClassName the class name which should be instanciated. Must be a subclass of
	 * @return \F3\FLOW3\MVC\Web\SubRequest The sub request as an object
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function build(\F3\FLOW3\MVC\Web\Request $parentRequest, $argumentNamespace = '', $subRequestClassName = 'F3\FLOW3\MVC\Web\SubRequest') {
		$subRequest = $this->objectManager->create($subRequestClassName, $parentRequest);
		$subRequest->setMethod($this->environment->getRequestMethod());
		$subRequest->setArgumentNamespace($argumentNamespace);

		$this->setArgumentsFromRawRequestData($subRequest);
		$this->setControllerKeysAndFormat($subRequest);
		return $subRequest;
	}

	/**
	 * Sets the arguments (GET & POST) from the parent request that are
	 * prefixed with the current argument namespace
	 *
	 * @param \F3\FLOW3\MVC\Web\SubRequest $subRequest
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setArgumentsFromRawRequestData(\F3\FLOW3\MVC\Web\SubRequest $subRequest) {
		$parentRequest = $subRequest->getParentRequest();
		$argumentNamespace = $subRequest->getArgumentNamespace();
		if ($parentRequest->hasArgument($argumentNamespace) === FALSE || !is_array($parentRequest->getArgument($argumentNamespace))) {
			return;
		}
		$subRequest->setArguments($parentRequest->getArgument($argumentNamespace));
	}

	/**
	 * Sets package key, subpackage key, controller name, action name and format
	 * of the current request.
	 *
	 * @param \F3\FLOW3\MVC\Web\SubRequest $subRequest
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setControllerKeysAndFormat(\F3\FLOW3\MVC\Web\SubRequest $subRequest) {
		foreach($subRequest->getArguments() as $argumentName => $argumentValue) {
			switch ($argumentName) {
				case '@package' :
					$subRequest->setControllerPackageKey($argumentValue);
				break;
				case '@subpackage' :
					$subRequest->setControllerSubpackageKey($argumentValue);
				break;
				case '@controller' :
					$subRequest->setControllerName($argumentValue);
				break;
				case '@action' :
					$subRequest->setControllerActionName($argumentValue);
				break;
				case '@format' :
					$subRequest->setFormat(strtolower($argumentValue));
				break;
			}
		}
	}
}
?>
