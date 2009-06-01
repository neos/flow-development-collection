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
 * @package Kickstart
 * @version $Id: ManualController.php 2465 2009-05-29 11:40:02Z k-fish $
 */

/**
 * Controller for the Kickstart generator
 *
 * @package Kickstart
 * @version $Id: ManualController.php 2465 2009-05-29 11:40:02Z k-fish $
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
	 * Error action - display the help message
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function errorAction() {
		$this->indexAction();
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
			'Usage: php index.php kickstart controller --package-key <package-key> [--controller-name <controller-name>]' . PHP_EOL .  PHP_EOL
		);
	}

	/**
	 * Generate a controller for a package
	 *
	 * @param string $packageKey The package key of the package for the new controller
	 * @param string $name The name for the new controller
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function controllerAction($packageKey, $controllerName = 'Standard') {
		if (!$this->packageManager->isPackageAvailable($packageKey)) {
			return 'Package "' . $packageKey . '" is not available.';
		}
		$generatedFiles = $this->generatorService->generateController($packageKey, $controllerName);
		return implode(PHP_EOL, $generatedFiles) . PHP_EOL;
	}
}
?>
