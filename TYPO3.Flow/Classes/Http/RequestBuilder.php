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
 * Builds a web request object from the raw HTTP information
 *
 * @FLOW3\Scope("singleton")
 */
class RequestBuilder {

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Routing\RouterInterface
	 */
	protected $router;

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
	 * Injects the configuration manager
	 *
	 * @param \TYPO3\FLOW3\Configuration\ConfigurationManager $configurationManager A reference to the configuration manager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\FLOW3\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects a router for routing the web request
	 *
	 * @param \TYPO3\FLOW3\MVC\Web\Routing\RouterInterface $router A router which routes the web request to a controller and action
	 * @return void
	 */
	public function injectRouter(\TYPO3\FLOW3\MVC\Web\Routing\RouterInterface $router) {
		$this->router = $router;
	}

	/**
	 * Builds a web request object from the raw HTTP information
	 *
	 * @return \TYPO3\FLOW3\MVC\Web\Request The web request as an object
	 */
	public function build() {
		$this->emitBeforeBuild();
		$request = new \TYPO3\FLOW3\MVC\Web\Request();
		$request->setRequestUri($this->environment->getRequestUri());
		$request->setBaseUri($this->environment->getBaseUri());
		$request->setMethod($this->environment->getRequestMethod());

		$this->setArgumentsFromRawRequestData($request);

		$routesConfiguration = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
		$this->router->setRoutesConfiguration($routesConfiguration);
		$this->router->route($request);

		$this->emitAfterBuild();
		return $request;
	}

	/**
	 * This signal is triggered before building a web request. It is mainly useful
	 * for collecting profiling information.
	 *
	 * @return void
	 * @FLOW3\Signal
	 */
	public function emitBeforeBuild() {}

	/**
	 * This signal is triggered after building a web request. It is mainly useful
	 * for collecting profiling information.
	 *
	 * @return void
	 * @FLOW3\Signal
	 */
	public function emitAfterBuild() {}

	/**
	 * Takes the raw request data and - depending on the request method
	 * maps them into the request object. Afterwards all mapped arguments
	 * can be retrieved by the getArgument(s) method, no matter if they
	 * have been GET, POST or PUT arguments before.
	 *
	 * @param \TYPO3\FLOW3\MVC\Web\Request $request The web request which will contain the arguments
	 * @return void
	 */
	protected function setArgumentsFromRawRequestData(\TYPO3\FLOW3\MVC\Web\Request $request) {
		$arguments = $request->getRequestUri()->getArguments();
		if ($request->getMethod() === 'POST') {
			$postArguments = $this->environment->getRawPostArguments();
			$arguments = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($arguments, $postArguments);

			$uploadArguments = $this->environment->getUploadedFiles();
			$arguments = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($arguments, $uploadArguments);
		}
		$request->setArguments($arguments);
	}
}
?>