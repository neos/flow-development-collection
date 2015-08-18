<?php
namespace TYPO3\Flow\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Core\Booting\Scripts;

/**
 * Command controller for starting the development-server
 *
 * @Flow\Scope("singleton")
 */
class ServerCommandController extends CommandController {


	/**
	 * @Flow\InjectConfiguration
	 * @var array
	 */
	protected $settings;

	/**
	 * Run a standalone development server
	 *
	 * Starts an embedded server, see http://php.net/manual/en/features.commandline.webserver.php
	 * Note: This requires PHP 5.4+
	 *
	 * To change the context Flow will run in, you can set the <b>FLOW_CONTEXT</b> environment variable:
	 * <i>export FLOW_CONTEXT=Development && ./flow server:run</i>
	 *
	 * @param string $host The host name or IP address for the server to listen on
	 * @param integer $port The server port to listen on
	 * @return void
	 */
	public function runCommand($host = '127.0.0.1', $port = 8081) {
		$command = Scripts::buildPhpCommand($this->settings);

		$address = sprintf('%s:%s', $host, $port);
		$command .= ' -S ' . escapeshellarg($address) . ' -t ' . escapeshellarg(FLOW_PATH_WEB) . ' ' . escapeshellarg(FLOW_PATH_FLOW . '/Scripts/PhpDevelopmentServerRouter.php');

		$this->outputLine('Server running. Please go to <b>http://' . $address . '</b> to browse the application.');
		exec($command);
	}
}