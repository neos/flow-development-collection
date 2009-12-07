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
 * Builds a web request object from the raw HTTP information
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestBuilder {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface $objectFactory
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \F3\FLOW3\Configuration\Manager
	 */
	protected $configurationManager;

	/**
	 * @var \F3\FLOW3\MVC\Web\RouterInterface
	 */
	protected $router;

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory A reference to the object factory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the server environment
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment The environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param \F3\FLOW3\Configuration\Manager $configurationManager A reference to the configuration manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(\F3\FLOW3\Configuration\Manager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects a router for routing the web request
	 *
	 * @param \F3\FLOW3\MVC\Web\Routing\RouterInterface $router A router which routes the web request to a controller and action
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectRouter(\F3\FLOW3\MVC\Web\Routing\RouterInterface $router) {
		$this->router = $router;
	}

	/**
	 * Builds a web request object from the raw HTTP information
	 *
	 * @return \F3\FLOW3\MVC\Web\Request The web request as an object
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function build() {
		$request = $this->objectFactory->create('F3\FLOW3\MVC\Web\Request');
		$request->injectEnvironment($this->environment);
		$request->setRequestUri($this->environment->getRequestUri());
		$request->setMethod($this->environment->getRequestMethod());
		$this->setArgumentsFromRawRequestData($request);

		$routesConfiguration = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_ROUTES);
		$this->router->setRoutesConfiguration($routesConfiguration);
		$this->router->route($request);

		return $request;
	}

	/**
	 * Takes the raw request data and - depending on the request method
	 * maps them into the request object. Afterwards all mapped arguments
	 * can be retrieved by the getArgument(s) method, no matter if they
	 * have been GET, POST or PUT arguments before.
	 *
	 * @param \F3\FLOW3\MVC\Web\Request $request The web request which will contain the arguments
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setArgumentsFromRawRequestData(\F3\FLOW3\MVC\Web\Request $request) {
		foreach ($request->getRequestUri()->getArguments() as $argumentName => $argumentValue) {
			$request->setArgument($argumentName, $argumentValue);
		}
		switch ($request->getMethod()) {
			case 'POST' :
				foreach ($this->environment->getRawPostArguments() as $argumentName => $argumentValue) {
					$request->setArgument($argumentName, $argumentValue);
				}
				foreach ($this->environment->getUploadedFiles() as $argumentName => $argumentValue) {
					if ($request->hasArgument($argumentName)) {
						$existingArgumentValue = $request->getArgument($argumentName);
						if (is_array($existingArgumentValue)) {
							$request->setArgument($argumentName, \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($existingArgumentValue, $argumentValue));
						}
					} else {
						$request->setArgument($argumentName, $argumentValue);
					}
				}
				break;
#			case 'PUT' :
#				$putArguments = array();
#				parse_str(file_get_contents("php://input"), $putArguments);
#				foreach ($putArguments as $argumentName => $argumentValue) {
#					$request->setArgument($argumentName, $argumentValue);
#				}
#			break;
		}
	}
}
?>