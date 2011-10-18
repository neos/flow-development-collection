<?php
namespace TYPO3\FLOW3\MVC\Web;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Builds a web sub request object
 *
 * @FLOW3\Scope("singleton")
 */
class SubRequestBuilder {

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * Injects the object factory
	 *
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager A reference to the object factory
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the server environment
	 *
	 * @param \TYPO3\FLOW3\Utility\Environment $environment The environment
	 * @return void
	 */
	public function injectEnvironment(\TYPO3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Builds a sub request object. If you define the SubRequestClassName,
	 * the class specified MUST be a subclass of TYPO3\FLOW3\MVC\Web\SubRequest
	 *
	 * @param \TYPO3\FLOW3\MVC\Web\Request $parentRequest
	 * @param string $argumentNamespace namespace that will be prefixed to URIs of this sub request
	 * @param string $subRequestClassName the class name which should be instanciated. Must be a subclass of
	 * @return \TYPO3\FLOW3\MVC\Web\SubRequest The sub request as an object
	 */
	public function build(\TYPO3\FLOW3\MVC\Web\Request $parentRequest, $argumentNamespace = '', $subRequestClassName = 'TYPO3\FLOW3\MVC\Web\SubRequest') {
		$subRequest = new $subRequestClassName($parentRequest);
		$subRequest->setArgumentNamespace($argumentNamespace);

		$this->setArgumentsFromRawRequestData($subRequest);
		$this->setControllerKeysAndFormat($subRequest);
		return $subRequest;
	}

	/**
	 * Sets the arguments (GET & POST) from the parent request that are
	 * prefixed with the current argument namespace
	 *
	 * @param \TYPO3\FLOW3\MVC\Web\SubRequest $subRequest
	 * @return void
	 */
	protected function setArgumentsFromRawRequestData(\TYPO3\FLOW3\MVC\Web\SubRequest $subRequest) {
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
	 * @param \TYPO3\FLOW3\MVC\Web\SubRequest $subRequest
	 * @return void
	 */
	protected function setControllerKeysAndFormat(\TYPO3\FLOW3\MVC\Web\SubRequest $subRequest) {
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
