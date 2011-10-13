<?php
namespace TYPO3\FLOW3\Command;

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
 * Command controller for tasks related to routing
 *
 * @FLOW3\Scope("singleton")
 */
class RoutingCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\MVC\Web\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * List the known routes
	 *
	 * This command displays a list of all currently registered routes.
	 *
	 * @return void
	 */
	public function listCommand() {
		$routesConfiguration = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
		$this->router->setRoutesConfiguration($routesConfiguration);

		$this->outputLine('Currently registered routes:');
		foreach ($this->router->getRoutes() as $route) {
			$uriPattern = $route->getUriPattern();
			$this->outputLine(str_pad($uriPattern, 80) . $route->getName());
		}
	}
}

?>